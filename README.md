# SteamCategoriser
Add categories to your steam library based upon tags, ratings, genres, features etc.
---
You will need to get a steam API key: (https://steamcommunity.com/dev)
Insert your key in /api/steam/key.php

The initial load is quite slow as it scrapes, but games are then cached and recached after a year.

Once your library has been loaded, click "View VDF Format" and copy it to your steam library's categories vdf.
($STEAM/userdata/$UID/7/remote/sharedconfig.vdf) where $STEAM is your steam install directory and $UID is your steam account id. 

-NOTE: only the Apps tag and its sub elements are generated at present; just replace this with the generated code. If you don't see the Apps tag, you may need to favourite or add a category to a game to generate it.

## WARNING
You will lose all preexisting tags and favourited games.
