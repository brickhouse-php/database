<?php

namespace Brickhouse\Database;

use Brickhouse\Log\Log;

abstract class DatabaseConnection implements Queryable
{
    /**
     * Unique identifier which identifies the connection.
     *
     * @var string
     */
    public readonly string $id;

    /**
     * The active PDO connection.
     *
     * @var null|\PDO
     */
    protected null|\PDO $pdo = null;

    /**
     * The default fetch mode for the connection.
     *
     * @var int
     */
    protected int $fetchMode = \PDO::FETCH_ASSOC;

    /**
     * Defines whether to log SQL statements.
     *
     * @var bool
     */
    protected private(set) bool $loggingStatements = false;

    /**
     * Contains all logged queries on the connection.
     *
     * @var array<int,array{query:string,bindings:array<int,mixed>}>
     */
    private array $statements = [];

    /**
     * Gets the grammar processor for the database connection.
     *
     * @var Grammar
     */
    abstract public Grammar $grammar { get; }

    /**
     * Connect to the database using PHP's PDO interface.
     *
     * @param DatabaseConnectionString     $connectionString
     */
    public function __construct(
        protected DatabaseConnectionString $connectionString,
    ) {
        $this->id = random_byte_string();

        $this->connect();
    }

    /**
     * Attempt to connect to the database with the given database connection.
     *
     * If a connection is already established, nothing is done.
     *
     * @return void
     */
    public function connect(): void
    {
        if ($this->pdo !== null) {
            return;
        }

        $this->pdo = new \PDO(
            $this->connectionString->connectionString,
            $this->connectionString->username,
            $this->connectionString->password
        );
    }

    /**
     * Create a prepared `PDOStatement` with the given SQL query content.
     *
     * @param string $query
     *
     * @return \PDOStatement
     */
    protected function prepared(string $query): \PDOStatement
    {
        return $this->pdo->prepare($query);
    }

    /**
     * @inheritDoc
     */
    public function select(string $query, array $bindings = []): array
    {
        return $this->execute(
            fn($statement) => $statement->fetchAll($this->fetchMode),
            $query,
            $bindings
        );
    }

    /**
     * @inheritDoc
     */
    public function selectSingle(string $query, array $bindings = []): array|false
    {
        return $this->execute(
            fn($statement) => $statement->fetch($this->fetchMode),
            $query,
            $bindings
        );
    }

    /**
     * @inheritDoc
     */
    public function statement(string $query, array $bindings = []): bool
    {
        return $this->execute(
            fn($statement, $result) => $result,
            $query,
            $bindings
        );
    }

    /**
     * @inheritDoc
     */
    public function affectingStatement(string $query, array $bindings = []): int
    {
        return $this->execute(
            fn($statement) => $statement->rowCount(),
            $query,
            $bindings
        );
    }

    /**
     * Executes the given query on the connection.
     *
     * @template TReturn
     *
     * @param \Closure(\PDOStatement, bool): TReturn    $callback
     * @param non-empty-string                          $query
     * @param array<int|string,mixed>                   $bindings
     *
     * @return TReturn
     */
    protected function execute(\Closure $callback, string $query, array $bindings = [])
    {
        $statement = $this->prepared($query);

        $bindings = array_values($bindings);

        [$result, $duration] = $this->measureQueryExecution(
            fn() => $statement->execute($bindings)
        );

        $this->logQueryExecution($query, $bindings, $duration);

        return $callback($statement, $result);
    }

    /**
     * Measure the execution time of the given query execution callback.
     *
     * @template TReturn
     *
     * @param \Closure():   TReturn     $callback
     *
     * @return array{0:TReturn,1:float}
     */
    private function measureQueryExecution(\Closure $callback): array
    {
        $start = hrtime(true);

        $result = $callback();

        return [$result, (hrtime(true) - $start) / 1_000_000];
    }

    /**
     * Logs the given query parameters, if query logging is enabled.
     *
     * @param string            $query
     * @param array<int,mixed>  $bindings
     * @param float             $duration
     *
     * @return void
     */
    private function logQueryExecution(string $query, array $bindings, float $duration): void
    {
        if (!$this->loggingStatements) {
            return;
        }

        $this->statements[] = compact('query', 'bindings', 'duration');
    }

    /**
     * Enables query logging on the connection.
     *
     * @return void
     */
    public final function enableQueryLogging(): void
    {
        $this->loggingStatements = true;
    }

    /**
     * Disables query logging on the connection.
     *
     * @return void
     */
    public final function disableQueryLogging(): void
    {
        $this->loggingStatements = false;
    }

    /**
     * Gets the logged queries on the connection.
     * If query logging is disabled, the returned array may be empty.
     *
     * @return array<int,array{query:string,bindings:array<int|string,mixed>}>
     *
     * @see \Brickhouse\Database\DatabaseConnection::enableQueryLogging()
     * @see \Brickhouse\Database\DatabaseConnection::disableQueryLogging()
     */
    public final function getQueryLog(): array
    {
        return $this->statements;
    }

    /**
     * Attempts to begin a transaction on the database connection.
     *
     * @return void
     */
    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    /**
     * Commits the current transaction to the database.
     *
     * @return void
     */
    public function commit(): void
    {
        if (!$this->pdo->inTransaction()) {
            return;
        }

        $this->pdo->commit();
    }

    /**
     * Rolls back the current transaction.
     *
     * @return void
     */
    public function rollback(): void
    {
        if (!$this->pdo->inTransaction()) {
            return;
        }

        $this->pdo->rollback();
    }

    /**
     * Executes the given callback within a transaction. If the callback fails, the transaction is rolled back.
     *
     * @param \Closure(): void      $callback
     *
     * @return void
     */
    public function transaction(\Closure $callback): void
    {
        $this->beginTransaction();

        try {
            $callback();
        } catch (\Throwable $e) {
            Log::error("Failed to commit transaction: {message}", [
                'message' => $e->getMessage()
            ]);

            $this->rollback();
        }

        $this->commit();
    }
}
