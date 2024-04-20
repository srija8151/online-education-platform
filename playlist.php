<?php

include 'components/connect.php';

if (isset($_COOKIE['user_id'])) {
    $user_id = $_COOKIE['user_id'];
} else {
    $user_id = '';
}

if (isset($_GET['get_id'])) {
    $get_id = $_GET['get_id'];
} else {
    $get_id = '';
    header('location:home.php');
}

if (isset($_POST['save_list'])) {
    if ($user_id != '') {
        $list_id = $_POST['list_id'];
        $list_id = filter_var($list_id, FILTER_SANITIZE_STRING);

        $select_list = $conn->prepare("SELECT * FROM `bookmark` WHERE user_id = ? AND playlist_id = ?");
        $select_list->execute([$user_id, $list_id]);

        if ($select_list->rowCount() > 0) {
            $remove_bookmark = $conn->prepare("DELETE FROM `bookmark` WHERE user_id = ? AND playlist_id = ?");
            $remove_bookmark->execute([$user_id, $list_id]);
            $message[] = 'Playlist removed!';
        } else {
            $insert_bookmark = $conn->prepare("INSERT INTO `bookmark`(user_id, playlist_id) VALUES(?,?)");
            $insert_bookmark->execute([$user_id, $list_id]);
            $message[] = 'Playlist saved!';
        }
    } else {
        $message[] = 'Please login first!';
    }
}

// Check if the video is finished and display the test
if (isset($_GET['video_finished'])) {
    // Display test here
    echo '<h2>Take the test</h2>';
    echo '<form action="" method="post">';
    echo '<input type="hidden" name="list_id" value="' . $get_id . '">';
    echo '<button type="submit" name="submit_test">Submit Test</button>';
    echo '</form>';
}

if (isset($_POST['submit_test'])) {
    // Assuming you have a function to evaluate the test results
    $test_results = evaluateTest($_POST); // Implement your evaluation logic

    if ($test_results['passed']) {
        // Assuming you have a function to generate the certificate
        generateCertificate($user_id, $get_id); // You may need to pass relevant parameters
        $message[] = 'Test passed! Certificate generated.';
    } else {
        $message[] = 'Test failed. Please try again.';
    }
}

function evaluateTest($post_data)
{
    // Implement your test evaluation logic and return results (e.g., passed or failed)
    // You may also return additional information like the score
    // Example: return ['passed' => true, 'score' => 90];
}

function generateCertificate($user_id, $playlist_id)
{
    // Implement your certificate generation logic
    // You may use a library or create a certificate template
    // Save or display the certificate as needed
    // Example: saveCertificateToFile($user_id, $playlist_id);
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Playlist</title>

    <!-- Font Awesome CDN link  -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">

    <!-- Custom CSS file link  -->
    <link rel="stylesheet" href="css/style.css">

</head>

<body>

    <?php include 'components/user_header.php'; ?>

    <!-- Playlist section starts -->

    <section class="playlist">

        <h1 class="heading">Playlist details</h1>

        <div class="row">

            <?php
            $select_playlist = $conn->prepare("SELECT * FROM `playlist` WHERE id = ? and status = ? LIMIT 1");
            $select_playlist->execute([$get_id, 'active']);
            if ($select_playlist->rowCount() > 0) {
                $fetch_playlist = $select_playlist->fetch(PDO::FETCH_ASSOC);

                $playlist_id = $fetch_playlist['id'];

                $count_videos = $conn->prepare("SELECT * FROM `content` WHERE playlist_id = ?");
                $count_videos->execute([$playlist_id]);
                $total_videos = $count_videos->rowCount();

                $select_tutor = $conn->prepare("SELECT * FROM `tutors` WHERE id = ? LIMIT 1");
                $select_tutor->execute([$fetch_playlist['tutor_id']]);
                $fetch_tutor = $select_tutor->fetch(PDO::FETCH_ASSOC);

                $select_bookmark = $conn->prepare("SELECT * FROM `bookmark` WHERE user_id = ? AND playlist_id = ?");
                $select_bookmark->execute([$user_id, $playlist_id]);

            ?>

                <div class="col">
                    <form action="" method="post" class="save-list">
                        <input type="hidden" name="list_id" value="<?= $playlist_id; ?>">
                        <?php
                        if ($select_bookmark->rowCount() > 0) {
                        ?>
                            <button type="submit" name="save_list"><i class="fas fa-bookmark"></i><span>Saved</span></button>
                        <?php
                        } else {
                        ?>
                            <button type="submit" name="save_list"><i class="far fa-bookmark"></i><span>Save Playlist</span></button>
                        <?php
                        }
                        ?>
                    </form>
                    <div class="thumb">
                        <span><?= $total_videos; ?> videos</span>
                        <img src="uploaded_files/<?= $fetch_playlist['thumb']; ?>" alt="">
                    </div>
                </div>

                <div class="col">
                    <div class="tutor">
                        <img src="uploaded_files/<?= $fetch_tutor['image']; ?>" alt="">
                        <div>
                            <h3><?= $fetch_tutor['name']; ?></h3>
                            <span><?= $fetch_tutor['profession']; ?></span>
                        </div>
                    </div>
                    <div class="details">
                        <h3><?= $fetch_playlist['title']; ?></h3>
                        <p><?= $fetch_playlist['description']; ?></p>
                        <div class="date"><i class="fas fa-calendar"></i><span><?= $fetch_playlist['date']; ?></span></div>
                    </div>
                </div>

            <?php
            } else {
                echo '<p class="empty">This playlist was not found!</p>';
            }
            ?>

        </div>

    </section>

    <!-- Playlist section ends -->

    <!-- Videos container section starts -->

    <section class="videos-container">

        <h1 class="heading">Playlist videos</h1>

        <div class="box-container">

            <?php
            $select_content = $conn->prepare("SELECT * FROM `content` WHERE playlist_id = ? AND status = ? ORDER BY date DESC");
            $select_content->execute([$get_id, 'active']);
            if ($select_content->rowCount() > 0) {
                while ($fetch_content = $select_content->fetch(PDO::FETCH_ASSOC)) {
            ?>
                    <a href="watch_video.php?get_id=<?= $fetch_content['id']; ?>" class="box">
                        <i class="fas fa-play"></i>
                        <img src="uploaded_files/<?= $fetch_content['thumb']; ?>" alt="">
                        <h3><?= $fetch_content['title']; ?></h3>
                    </a>
            <?php
                }
            } else {
                echo '<p class="empty">No videos added yet!</p>';
            }
            ?>

        </div>

    </section>

    <!-- Videos container section ends -->

    <?php include 'components/footer.php'; ?>

    <!-- Custom JS file link  -->
    <script src="js/script.js"></script>

</body>

</html>
