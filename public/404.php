<?php
require_once __DIR__ . '/../config/bootstrap.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Page Not Found</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Arvo">
    <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('assets/404/style.css'), ENT_QUOTES, 'UTF-8'); ?>">
    <script src="https://unpkg.com/@lottiefiles/dotlottie-wc@0.8.11/dist/dotlottie-wc.js" type="module"></script>
</head>

<body>
    <section class="page_404">
        <div class="container">
            <div class="row">
                <div class="col-sm-12">
                    <div class="col-sm-10 col-sm-offset-1 text-center">
                        <div class="four_zero_four_bg">
                            <dotlottie-wc
                                class="lottie_404"
                                src="https://lottie.host/0ea8afe8-0acd-44d3-b8ac-8089ebad915e/YJPfyrVZR8.lottie"
                                autoplay
                                loop></dotlottie-wc>
                            <h1 class="text-center">404</h1>
                        </div>
                        <div class="contant_box_404">
                            <h3 class="h2">Look like you're lost</h3>
                            <p>the page you are looking for not avaible!</p>
                            <a href="<?= htmlspecialchars(route_url(''), ENT_QUOTES, 'UTF-8'); ?>" class="link_404">Go to Home</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</body>

</html>