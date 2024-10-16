<?php
$conn = new mysqli("localhost", "root", "", "song_file"); // Update with your database credentials

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

for ($i = ord('A'); $i <= ord('Z'); $i++) {
    $alpha = chr($i);
    $url = "https://friendstamilmp3.in/index.php?page=A-Z%20Movie%20Songs&cpage=$alpha";
    $data = file_get_contents($url);
    $extract_data = preg_match_all('/<span class="folder"><a href="[^"]*">(.*?)<\/a><\/span>/', $data, $matches);

    if ($extract_data) {
        foreach ($matches[1] as $matched_text) {
            // Insert movie name into the database
            $stmt = $conn->prepare("INSERT INTO `movie_name` (`movie_name`, `time_stamp`) VALUES (?, NOW())");
            $stmt->bind_param("s", $matched_text);

            if ($stmt->execute()) {
                echo "Inserted movie: $matched_text<br>";

                // Get the last inserted movie ID
                $movieId = $stmt->insert_id;

                // Fetch song names for the inserted movie
                $urltwo = "https://friendstamilmp3.in/index.php?page=A-Z%20Movie%20Songs&spage=" . urlencode($matched_text);
                $datatwo = file_get_contents($urltwo);
                $extract_data_two = preg_match_all('/<span class="songlist"><a[^>]*>(.*?)<\/a><\/span>/', $datatwo, $song_matches);

                if ($extract_data_two) {
                    foreach ($song_matches[1] as $song_full_name) {
                        // Extract the song name before the '-' character
                        $song_name = explode(' -', $song_full_name)[0];

                        // Insert song name into the database
                        $stmt_song = $conn->prepare("INSERT INTO `song_name` (`movie_name_Id`, `song_name`, `status`, `time_stamp`) VALUES (?, ?, 'active', NOW())");
                        $stmt_song->bind_param("ss",  $matched_text, $song_name);

                        if ($stmt_song->execute()) {
                            echo "Inserted song: $song_name for movie: $matched_text<br>";
                        } else {
                            echo "Error inserting song: $song_name - " . $stmt_song->error . "<br>";
                        }

                        $stmt_song->close();
                    }
                } else {
                    echo "No songs found for movie: $matched_text<br>";
                }
            } else {
                echo "Error inserting movie: $matched_text - " . $stmt->error . "<br>";
            }

            $stmt->close();
        }
    } else {
        echo "No matches found for $alpha.<br>";
    }
}

$conn->close();
?>
