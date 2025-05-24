CREATE TABLE `USERS` (
  `user_id` int PRIMARY KEY AUTO_INCREMENT,
  `country_id` int,
  `age` int,
  `name` varchar(255),
  `username` varchar(255),
  `email` varchar(255),
  `password` varchar(255),
  `date_joined` datetime,
  `last_login` datetime,
  `follower_num` int,
  `subscription_type` varchar(255),
  `top_genre` varchar(255),
  `num_songs_liked` int,
  `most_played_artist` varchar(255),
  `image` varchar(255)
);

CREATE TABLE `PLAY_HISTORY` (
  `play_id` int PRIMARY KEY AUTO_INCREMENT,
  `user_id` int,
  `song_id` int,
  `playtime` datetime
);

CREATE TABLE `ARTISTS` (
  `artist_id` int PRIMARY KEY AUTO_INCREMENT,
  `name` varchar(255),
  `genre` varchar(255),
  `date_joined` datetime,
  `total_num_music` int,
  `total_albums` int,
  `listeners` int,
  `bio` text,
  `country_id` int,
  `image` varchar(255)
);

CREATE TABLE `ALBUMS` (
  `album_id` int PRIMARY KEY AUTO_INCREMENT,
  `artist_id` int,
  `title` varchar(255),
  `release_date` datetime,
  `genre` varchar(255),
  `music_number` int,
  `image` varchar(255)
);

CREATE TABLE `SONGS` (
  `song_id` int PRIMARY KEY AUTO_INCREMENT,
  `album_id` int,
  `title` varchar(255),
  `duration` int,
  `genre` varchar(255),
  `release_date` datetime,
  `rank` int,
  `image` varchar(255)
);

CREATE TABLE `PLAYLISTS` (
  `playlist_id` int PRIMARY KEY AUTO_INCREMENT,
  `user_id` int,
  `title` varchar(255),
  `description` text,
  `date_created` datetime,
  `image` varchar(255)
);

CREATE TABLE `PLAYLIST_SONGS` (
  `playlistsong_id` int PRIMARY KEY AUTO_INCREMENT,
  `playlist_id` int,
  `song_id` int,
  `date_added` datetime
);

CREATE TABLE `COUNTRY` (
  `country_id` int PRIMARY KEY AUTO_INCREMENT,
  `country_name` varchar(255),
  `country_code` varchar(255)
);

ALTER TABLE `USERS` ADD FOREIGN KEY (`country_id`) REFERENCES `COUNTRY` (`country_id`);

ALTER TABLE `PLAY_HISTORY` ADD FOREIGN KEY (`user_id`) REFERENCES `USERS` (`user_id`);

ALTER TABLE `PLAY_HISTORY` ADD FOREIGN KEY (`song_id`) REFERENCES `SONGS` (`song_id`);

ALTER TABLE `ARTISTS` ADD FOREIGN KEY (`country_id`) REFERENCES `COUNTRY` (`country_id`);

ALTER TABLE `ALBUMS` ADD FOREIGN KEY (`artist_id`) REFERENCES `ARTISTS` (`artist_id`);

ALTER TABLE `SONGS` ADD FOREIGN KEY (`album_id`) REFERENCES `ALBUMS` (`album_id`);

ALTER TABLE `PLAYLISTS` ADD FOREIGN KEY (`user_id`) REFERENCES `USERS` (`user_id`);

ALTER TABLE `PLAYLIST_SONGS` ADD FOREIGN KEY (`playlist_id`) REFERENCES `PLAYLISTS` (`playlist_id`);

ALTER TABLE `PLAYLIST_SONGS` ADD FOREIGN KEY (`song_id`) REFERENCES `SONGS` (`song_id`);
