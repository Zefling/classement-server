<?php

namespace App\Enum;

enum Mode: string
{
    case Default = "default";
    case Teams = "teams";
    case Iceberg = "iceberg";
    case Axis = "axis";
    case Bingo = "bingo";
    case Columns = "columns";
}
