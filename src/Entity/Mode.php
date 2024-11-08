<?php

namespace App\Entity;

enum Mode: string
{
    case Default = "default";
    case Teams = "teams";
    case Iceberg = "iceberg";
    case Axis = "axis";
    case Bingo = "bingo";
    case Columns = "columns";
}
