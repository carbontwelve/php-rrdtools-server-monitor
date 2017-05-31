<!doctype html>

<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Stats</title>
    <link href="https://unpkg.com/ace-css/css/ace.min.css" rel="stylesheet">
    <style>
        .container{
            width:797px;
            margin: 30px auto;
            text-align:center
        }
    </style>
</head>
<body>

<div class="container">
    <h1 class="caps">Stats</h1>
    <h2 id="last_update" class="h5"></h2>
</div>

<div class="container">
    <div class="mb3">
        <?php foreach ($periods as $period) { ?>
            <a href="#" class="timeBtn btn <?= ($period === $periods[0] ? 'btn-primary' : '') ?>" data-time="<?= $period ?>"><?= $period ?></a>
        <?php } ?>
    </div>

    <?php foreach ($graphs as $name => $graph) { ?>
    <img id="<?= $name ?>" src="img/<?= $graph ?>hour.png" /><br><br>
    <?php } ?>
</div>

<script>
    var lastUpdate = document.getElementById('last_update');
    lastUpdate.textContent = '<?= $timestamp ?>';

    var timeButtons = document.querySelectorAll('.timeBtn');

    <?php foreach ($graphs as $name => $graph) { ?>
    var <?= $name ?> = document.querySelector('#<?= $name ?>');
    <?php } ?>

    for (var i = 0; i < timeButtons.length; i++) {
        timeButtons[i].addEventListener('click', function (e) {
            e.preventDefault();
            console.log(this.dataset.time);
            for (var n = 0; n < timeButtons.length; n++) {
                timeButtons[n].classList.remove('btn-primary');
            }

            this.classList.add('btn-primary');

            <?php foreach ($graphs as $name => $graph) { ?>
            <?= $name ?>.src = 'img/<?= $graph ?>' + this.dataset.time + '.png';
            <?php } ?>
        });
    }
</script>
</body>
</html>
