<?php
/**
 * Author: Fatma Terzi (20190702041)
 * generate_data.php – Part II (fixed)
 * Ensures unique usernames to avoid Duplicate Key errors.
 */

define('DB',  'fatma_terzi');
define('OUT', 'output.sql');

function readLines($file) {
    return array_filter(array_map('trim', @file($file) ?: []));
}

// Minimum record counts
$minUsers     = 100;
$minArtists   = 100;
$minAlbums    = 200;
$minSongs     = 1000;
$minPlaylists = 500;
$minHistory   = 100;

$sql = "";

// --------------------------------------------------
// 1) COUNTRY
// --------------------------------------------------
$countries = readLines('input/countries.txt');
foreach ($countries as $i => $line) {
    list($name, $code) = array_map('trim', explode(',', $line, 2));
    $id = $i + 1;
    $sql .= "INSERT INTO COUNTRY (country_id,country_name,country_code)\n"
          . "  VALUES ($id,'$name','$code');\n";
}

// --------------------------------------------------
// 2) USERS (with uniqueness)
// --------------------------------------------------
$names = readLines('input/names.txt');
$ages  = readLines('input/ages.txt');

$usedUsernames = [];  // track generated usernames

for ($i = 1; $i <= $minUsers; $i++) {
    // pick random name and age
    $nm  = $names[array_rand($names)];
    $age = $ages[array_rand($ages)];
    $cid = rand(1, count($countries));

    // generate a unique username
    do {
      $uname = strtolower(substr(str_replace(' ', '', $nm),0,3)) . rand(100,999);
    } while (in_array($uname, $usedUsernames));
    $usedUsernames[] = $uname;

    $email  = $uname . '@example.com';
    $pwd    = password_hash('pass123', PASSWORD_BCRYPT);
    $joined = date('Y-m-d', strtotime('-'.rand(1,365).' days'));

    $sql   .= "INSERT INTO USERS (country_id,age,name,username,email,password,date_joined)\n"
            . "  VALUES ($cid,$age,'$nm','$uname','$email','$pwd','$joined');\n";
}

// --------------------------------------------------
// 3) ARTISTS
// --------------------------------------------------
$artistLines = readLines('input/artists.txt');
for ($i = 1; $i <= $minArtists; $i++) {
    $line              = $artistLines[array_rand($artistLines)];
    list($aname,$agenre)= array_map('trim', explode(',', $line, 2));
    $cid               = rand(1, count($countries));
    $joined            = date('Y-m-d', strtotime('-'.rand(100,1000).' days'));
    $totalMusic        = rand(5,200);
    $totalAlbums       = rand(1,20);
    $listeners         = rand(1000,1000000);
    $bio               = "Bio of $aname.";
    $img               = "https://picsum.photos/seed/artist$i/200";

    $sql .= "INSERT INTO ARTISTS "
          . "(artist_id,name,genre,date_joined,total_num_music,total_albums,listeners,bio,country_id,image)\n"
          . "  VALUES ($i,'$aname','$agenre','$joined',$totalMusic,$totalAlbums,$listeners,'$bio',$cid,'$img');\n";
}

// --------------------------------------------------
// 4) ALBUMS
// --------------------------------------------------
for ($i = 1; $i <= $minAlbums; $i++) {
    $artistId = rand(1, $minArtists);
    $aname    = "Album $i";
    $rdate    = date('Y-m-d', strtotime('-'.rand(50,2000).' days'));
    $genre    = ['Pop','Rock','Jazz','Classical','Hip-Hop'][array_rand(range(0,4))];
    $numMusic = rand(5,20);
    $img      = "https://picsum.photos/seed/album$i/200";

    $sql .= "INSERT INTO ALBUMS "
          . "(album_id,artist_id,name,release_date,genre,music_number,image)\n"
          . "  VALUES ($i,$artistId,'$aname','$rdate','$genre',$numMusic,'$img');\n";
}

// --------------------------------------------------
// 5) SONGS
// --------------------------------------------------
for ($i = 1; $i <= $minSongs; $i++) {
    $albumId  = rand(1,$minAlbums);
    $title    = "Song $i";
    $duration = rand(120,360);
    $genre    = ['Pop','Rock','Jazz','Classical','Hip-Hop'][array_rand(range(0,4))];
    $rdate    = date('Y-m-d', strtotime('-'.rand(10,2000).' days'));
    $rank     = rand(1,1000);
    $img      = "https://picsum.photos/seed/song$i/200";

    $sql .= "INSERT INTO SONGS "
          . "(song_id,album_id,title,duration,genre,release_date,rank,image)\n"
          . "  VALUES ($i,$albumId,'$title',$duration,'$genre','$rdate',$rank,'$img');\n";
}

// --------------------------------------------------
// 6) PLAYLISTS
// --------------------------------------------------
$playlistTitles = readLines('input/playlists.txt');
for ($i = 1; $i <= $minPlaylists; $i++) {
    $uid   = rand(1,$minUsers);
    $title = $playlistTitles[array_rand($playlistTitles)];
    $desc  = "Playlist $title description.";
    $pdate = date('Y-m-d', strtotime('-'.rand(1,365).' days'));
    $img   = "https://picsum.photos/seed/pl$i/200";

    $sql .= "INSERT INTO PLAYLISTS "
          . "(playlist_id,user_id,title,description,date_created,image)\n"
          . "  VALUES ($i,$uid,'$title','$desc','$pdate','$img');\n";
}

// --------------------------------------------------
// 7) PLAYLIST_SONGS
// --------------------------------------------------
for ($pid = 1; $pid <= $minPlaylists; $pid++) {
    $count = rand(5,20);
    $added = [];
    for ($j = 0; $j < $count; $j++) {
        $sid = rand(1,$minSongs);
        if (!in_array($sid,$added)) {
            $added[] = $sid;
            $date    = date('Y-m-d', strtotime('-'.rand(1,365).' days'));
            $sql    .= "INSERT INTO PLAYLIST_SONGS "
                     . "(playlist_id,song_id,date_added)\n"
                     . "  VALUES ($pid,$sid,'$date');\n";
        }
    }
}

// --------------------------------------------------
// 8) PLAY_HISTORY
// --------------------------------------------------
for ($i = 1; $i <= $minHistory; $i++) {
    $uid  = rand(1,$minUsers);
    $sid  = rand(1,$minSongs);
    $time = date('Y-m-d H:i:s', strtotime('-'.rand(0,86400*365).' seconds'));
    $sql .= "INSERT INTO PLAY_HISTORY "
          . "(user_id,song_id,playtime)\n"
          . "  VALUES ($uid,$sid,'$time');\n";
}

// Write to output.sql
file_put_contents(OUT,$sql);
echo "output.sql oluşturuldu.\n";
