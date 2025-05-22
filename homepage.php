<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html"); exit;
}

$uid  = $_SESSION['user_id'];
$name = $_SESSION['name'];
$mysqli = new mysqli('localhost','root','','fatma_terzi');
if ($mysqli->connect_error) die($mysqli->connect_error);

// 1) Kullanıcının ülke_id'sini al
$stmt = $mysqli->prepare("SELECT country_id FROM USERS WHERE user_id=?");
$stmt->bind_param("i", $uid);
$stmt->execute();
$stmt->store_result();                   // ← ekledik
$stmt->bind_result($cid);
$stmt->fetch();
$stmt->close();

// 2) Playlistleri hazırla
$plstm = $mysqli->prepare("SELECT playlist_id,title,image FROM PLAYLISTS WHERE user_id=?");
$plstm->bind_param("i", $uid);
$plstm->execute();
$plstm->store_result();                  // ← ekledik
$plstm->bind_result($pid, $pt, $pi);

// 3) Son 10 çalınan şarkı
$search = trim($_GET['q_song'] ?? '');
if ($search !== '') {
    $like_start = $search . '%';
    $like_any = '%' . $search . '%';
    $hst = $mysqli->prepare("
        SELECT s.song_id, s.title, s.image
        FROM PLAY_HISTORY h
        JOIN SONGS s ON h.song_id=s.song_id
        WHERE h.user_id=?
        ORDER BY 
          (s.title LIKE ?) DESC,
          (s.title LIKE ?) DESC,
          h.playtime DESC
        LIMIT 10
    ");
    $hst->bind_param("iss", $uid, $like_start, $like_any);
} else {
    $hst = $mysqli->prepare("
        SELECT s.song_id, s.title, s.image
        FROM PLAY_HISTORY h
        JOIN SONGS s ON h.song_id=s.song_id
        WHERE h.user_id=?
        ORDER BY h.playtime DESC LIMIT 10
    ");
    $hst->bind_param("i", $uid);
}
$hst->execute();
$hst->store_result();                     // ← ekledik
$hst->bind_result($sid, $st, $si);

// 4) Ülkenin top 5 sanatçısı
$artist_search = trim($_GET['q_artist'] ?? '');
if ($artist_search !== '') {
    $like_start = $artist_search . '%';
    $like_any = '%' . $artist_search . '%';
    $ast = $mysqli->prepare("
        SELECT artist_id, name, listeners, image
        FROM ARTISTS
        WHERE country_id=?
        ORDER BY 
          (name LIKE ?) DESC,
          (name LIKE ?) DESC,
          listeners DESC
        LIMIT 5
    ");
    $ast->bind_param("iss", $cid, $like_start, $like_any);
} else {
    $ast = $mysqli->prepare("
        SELECT artist_id,name,listeners,image
        FROM ARTISTS
        WHERE country_id=?
        ORDER BY listeners DESC
        LIMIT 5
    ");
    $ast->bind_param("i", $cid);
}
$ast->execute();
$ast->store_result();                     // ← ekledik
$ast->bind_result($aid, $an, $al, $ai);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>Merhaba, <?=htmlspecialchars($name)?>!</title>
  <link rel="stylesheet" href="css/style.css">
  <style>
    html, body {
      height: 100%;
      margin: 0;
      padding: 0;
    }
    body {
      min-height: 100vh;
    }
    .container {
      display: flex;
      gap: 20px;
      min-height: 80vh;
      align-items: stretch;
    }
    .left, .right {
      background: #1a0033;
      border-radius: 10px;
      padding: 24px 16px 32px 16px;
      min-height: 900px;
      height: 100%;
      overflow: visible;
      flex: 1;
      display: flex;
      flex-direction: column;
    }
    .autocomplete-suggestions {
      max-height: 200px;
      overflow-y: auto;
      width: 100%;
      background: white;
      color: black;
      border: 1px solid #ccc;
      display: none;
      position: absolute;
      z-index: 1000;
      left: 0;
      top: 100%;
      box-sizing: border-box;
    }
    .autocomplete-suggestions div {
      padding: 5px;
      cursor: pointer;
      border-bottom: 1px solid #eee;
    }
    .autocomplete-suggestions div:last-child {
      border-bottom: none;
    }
    .autocomplete-suggestions div:hover {
      background: #eee;
    }
    .search-container { position: relative; width: 100%; }
    .add-button {
      background: #7c4dff;
      color: #fff;
      border: none;
      border-radius: 8px;
      padding: 8px 16px;
      cursor: pointer;
      font-size: 1.1em;
    }
    .popular-artists-feed {
      margin-bottom: 24px;
      padding: 10px 0 6px 0;
      background: #22004a;
      border-radius: 10px;
      min-height: 40px;
    }
    .artist-feed-list {
      list-style: none;
      margin: 0;
      padding: 0 0 0 10px;
    }
    .artist-feed-list li {
      margin: 6px 0;
    }
    .artist-feed-link {
      color: #aeeaff;
      font-size: 1.1em;
      text-decoration: none;
      padding: 2px 6px;
      border-radius: 4px;
      transition: background 0.2s, color 0.2s;
      cursor: pointer;
      display: inline-block;
    }
    .artist-feed-link:hover {
      background: #7c4dff;
      color: #fff;
    }
    .artist-feed-link.disabled {
      color: #888;
      pointer-events: none;
      background: none;
    }
  </style>
</head>
<body>
  <div class="header">
    <img src="images/logo.png" alt="Logo" class="header-logo">
    <span class="header-title">Harmony DB</span>
  </div>
  <h1>Merhaba, <?=htmlspecialchars($name)?>!</h1>
  <div class="container">
    <!-- Sol: Playlistler -->
    <div class="left">
      <div class="search-container" style="position:relative;width:100%;display:flex;gap:8px;">
        <input type="text" id="playlist_search" placeholder="Playlist ara..." class="search-input" autocomplete="off" style="width:100%;">
        <div id="playlist_suggestions" class="autocomplete-suggestions"></div>
        <button id="play_playlist" type="button" class="add-button">Çal</button>
        <button class="add-button" onclick="window.location.href='add_playlist.php'">+</button>
      </div>
      <h3>Playlistlerim</h3>
      <?php while($plstm->fetch()): ?>
        <div class="playlist">
          <img src="<?=htmlspecialchars($pi)?>" alt="">
          <a href="playlistpage.php?id=<?=$pid?>"><?=htmlspecialchars($pt)?></a>
        </div>
      <?php endwhile; ?>
      <?php $plstm->close(); ?>
    </div>

    <!-- Sağ: Geçmiş & Sanatçılar -->
    <div class="right">
      <!-- Şarkı arama kutusu -->
      <div class="search-container" style="position:relative;width:100%;">
        <form id="song_search_form" style="display:flex;gap:8px;">
          <input type="text" id="song_search" name="q_song" value="<?= htmlspecialchars($search) ?>" placeholder="Şarkı ara..." class="search-input" autocomplete="off" style="width:100%;">
          <div id="song_suggestions" class="autocomplete-suggestions"></div>
          <button type="submit" id="song_search_btn" class="add-button" style="width:auto;">Ara</button>
        </form>
      </div>
      <h3>Son 10 Çalınan Şarkı</h3>
      <?php while($hst->fetch()): ?>
        <div class="song">
          <img src="<?=htmlspecialchars($si)?>" alt="">
          <a href="currentmusic.php?id=<?=$sid?>"><?=htmlspecialchars($st)?></a>
        </div>
      <?php endwhile; ?>
      <?php $hst->close(); ?>

      <hr>

      <h3>Ülkenin En Çok Dinlenen 5 Sanatçısı</h3>
      <form id="artist_search_form" method="get" action="homepage.php" style="width:100%;display:flex;gap:8px;margin-bottom:8px;">
        <div class="search-container" style="position:relative;width:100%;">
          <input type="text" id="artist_search" name="q_artist" value="<?= htmlspecialchars($artist_search) ?>" placeholder="Sanatçı ara..." class="search-input" autocomplete="off" style="width:100%;">
          <div id="artist_suggestions" class="autocomplete-suggestions"></div>
        </div>
        <button type="submit" id="artist_search_btn" class="add-button" style="width:auto;">Ara</button>
      </form>
      <!-- Popüler sanatçılar feed -->
      <div class="popular-artists-feed">
        <?php
        $popular_artists = ['NOFX', 'Zedd', 'Blink-182', 'Pearl Jam', 'Rise Against'];
        $artist_ids = [];
        if (!empty($popular_artists)) {
          $in = str_repeat('?,', count($popular_artists) - 1) . '?';
          $stmt = $mysqli->prepare("SELECT artist_id, name FROM ARTISTS WHERE name IN ($in)");
          $stmt->bind_param(str_repeat('s', count($popular_artists)), ...$popular_artists);
          $stmt->execute();
          $res = $stmt->get_result();
          while($row = $res->fetch_assoc()) {
            $artist_ids[$row['name']] = $row['artist_id'];
          }
          $stmt->close();
        }
        ?>
        <ul class="artist-feed-list">
          <?php foreach ($popular_artists as $artist): ?>
            <?php if (isset($artist_ids[$artist])): ?>
              <li><a class="artist-feed-link" href="artistpage.php?id=<?= $artist_ids[$artist] ?>"><?= htmlspecialchars($artist) ?></a></li>
            <?php else: ?>
              <li><span class="artist-feed-link disabled"><?= htmlspecialchars($artist) ?></span></li>
            <?php endif; ?>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
  </div>
  <script>
  let selectedPlaylistId = null;
  let selectedSongId = null;
  let selectedArtistId = null;
  function setupPlaylistAutocomplete() {
    const input = document.getElementById('playlist_search');
    const suggestions = document.getElementById('playlist_suggestions');
    input.addEventListener('input', function() {
      var query = this.value;
      selectedPlaylistId = null;
      if (query.length === 0) {
        suggestions.style.display = 'none';
        return;
      }
      var xhr = new XMLHttpRequest();
      xhr.open('GET', 'search_playlist.php?q=' + encodeURIComponent(query), true);
      xhr.onload = function() {
        if (xhr.status === 200) {
          var results = JSON.parse(xhr.responseText);
          suggestions.innerHTML = '';
          if (results.length > 0) {
            results.forEach(function(playlist) {
              var div = document.createElement('div');
              div.textContent = playlist.title;
              div.onclick = function() {
                input.value = playlist.title;
                selectedPlaylistId = playlist.playlist_id;
                suggestions.style.display = 'none';
              };
              suggestions.appendChild(div);
            });
            suggestions.style.display = 'block';
          } else {
            suggestions.style.display = 'none';
          }
        }
      };
      xhr.send();
    });
    window.addEventListener('click', function(e) {
      if (!input.contains(e.target) && !suggestions.contains(e.target)) {
        suggestions.style.display = 'none';
      }
    });
  }
  setupPlaylistAutocomplete();
  document.getElementById('play_playlist').onclick = function() {
    if (selectedPlaylistId) {
      window.location.href = 'playlistpage.php?id=' + selectedPlaylistId;
    } else {
      alert('Lütfen önce bir playlist seçin.');
    }
  };
  // Şarkı arama autocomplete ve yönlendirme SADECE bu form için
  function setupSongAutocompleteAndRedirect() {
    const input = document.getElementById('song_search');
    const suggestions = document.getElementById('song_suggestions');
    const form = document.getElementById('song_search_form');
    input.addEventListener('input', function() {
      var query = this.value;
      selectedSongId = null;
      if (query.length === 0) {
        suggestions.style.display = 'none';
        return;
      }
      var xhr = new XMLHttpRequest();
      xhr.open('GET', 'search_song.php?q=' + encodeURIComponent(query), true);
      xhr.onload = function() {
        if (xhr.status === 200) {
          var results = JSON.parse(xhr.responseText);
          suggestions.innerHTML = '';
          if (results.length > 0) {
            results.forEach(function(song) {
              var div = document.createElement('div');
              div.textContent = song.title;
              div.onclick = function() {
                input.value = song.title;
                selectedSongId = song.song_id;
                suggestions.style.display = 'none';
              };
              suggestions.appendChild(div);
            });
            suggestions.style.display = 'block';
          } else {
            suggestions.style.display = 'none';
          }
        }
      };
      xhr.send();
    });
    window.addEventListener('click', function(e) {
      if (!input.contains(e.target) && !suggestions.contains(e.target)) {
        suggestions.style.display = 'none';
      }
    });
    // Ara butonuna basınca yönlendir
    form.addEventListener('submit', function(e) {
      e.preventDefault();
      var query = input.value.trim();
      if (!query) return;
      if (selectedSongId) {
        window.location.href = 'currentmusic.php?id=' + selectedSongId;
      } else {
        // AJAX ile id bul
        var xhr = new XMLHttpRequest();
        xhr.open('GET', 'search_song.php?q=' + encodeURIComponent(query), true);
        xhr.onload = function() {
          if (xhr.status === 200) {
            var results = JSON.parse(xhr.responseText);
            if (results.length > 0) {
              window.location.href = 'currentmusic.php?id=' + results[0].song_id;
            } else {
              alert('Şarkı bulunamadı!');
            }
          }
        };
        xhr.send();
      }
    });
  }
  setupSongAutocompleteAndRedirect();
  // Sanatçı autocomplete
  function setupArtistAutocompleteAndRedirect() {
    const input = document.getElementById('artist_search');
    const suggestions = document.getElementById('artist_suggestions');
    const form = document.getElementById('artist_search_form');
    input.addEventListener('input', function() {
      var query = this.value;
      selectedArtistId = null;
      if (query.length === 0) {
        suggestions.style.display = 'none';
        return;
      }
      var xhr = new XMLHttpRequest();
      xhr.open('GET', 'search_artist.php?q=' + encodeURIComponent(query), true);
      xhr.onload = function() {
        if (xhr.status === 200) {
          var results = JSON.parse(xhr.responseText);
          suggestions.innerHTML = '';
          if (results.length > 0) {
            results.forEach(function(artist) {
              var div = document.createElement('div');
              div.textContent = artist.name;
              div.onclick = function() {
                input.value = artist.name;
                selectedArtistId = artist.artist_id;
                suggestions.style.display = 'none';
              };
              suggestions.appendChild(div);
            });
            suggestions.style.display = 'block';
          } else {
            suggestions.style.display = 'none';
          }
        }
      };
      xhr.send();
    });
    window.addEventListener('click', function(e) {
      if (!input.contains(e.target) && !suggestions.contains(e.target)) {
        suggestions.style.display = 'none';
      }
    });
    // Ara butonuna basınca yönlendir
    form.addEventListener('submit', function(e) {
      e.preventDefault();
      var query = input.value.trim();
      if (!query) return;
      if (selectedArtistId) {
        window.location.href = 'artistpage.php?id=' + selectedArtistId;
      } else {
        // AJAX ile id bul
        var xhr = new XMLHttpRequest();
        xhr.open('GET', 'search_artist.php?q=' + encodeURIComponent(query), true);
        xhr.onload = function() {
          if (xhr.status === 200) {
            var results = JSON.parse(xhr.responseText);
            if (results.length > 0) {
              window.location.href = 'artistpage.php?id=' + results[0].artist_id;
            } else {
              alert('Sanatçı bulunamadı!');
            }
          }
        };
        xhr.send();
      }
    });
  }
  setupArtistAutocompleteAndRedirect();
  </script>
</body>
</html>
