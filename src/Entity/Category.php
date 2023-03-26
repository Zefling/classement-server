<?php

namespace App\Entity;

enum Category: string
{
    case Animal = "animal";
    case Anime = "anime";
    case BoardGame = "board.game";
    case Book = "book";
    case Brand = "brand";
    case Comics = "comics";
    case Computer = "computer";
    case Ecology = "ecology";
    case Entertainment = "entertainment";
    case Figure = "figure";
    case Food = "food";
    case Game = "game";
    case Geography = "geography";
    case History = "history";
    case Language = "language";
    case Manga = "manga";
    case Movie = "movie";
    case Music = "music";
    case People = "people";
    case Politics = "politics";
    case Place = "place";
    case Programming = "programming";
    case Roleplaying = "roleplaying";
    case Science = "science";
    case Series = "series";
    case Show = "show";
    case Sport = "sport";
    case Technology = "technology";
    case Vehicle = "vehicle";
    case VideoGame = "video.game";
    case Other = 'other';
}
