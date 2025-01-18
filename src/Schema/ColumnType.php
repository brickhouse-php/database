<?php

namespace Brickhouse\Database\Schema;

enum ColumnType
{
    case Char;
    case String;
    case TinyText;
    case Text;
    case MediumText;
    case LongText;
    case Integer;
    case TinyInteger;
    case SmallInteger;
    case MediumInteger;
    case BigInteger;
    case Float;
    case Double;
    case Date;
    case Timestamp;
    case TimestampTz;
}
