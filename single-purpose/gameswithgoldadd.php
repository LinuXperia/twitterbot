<?php
require_once('gameswithgold.inc.php');

$sError = '';
$sSuccess = '';

//connect to database
try {
    $oPDO = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);
} catch (Exception $e) {
    $sError = 'Database connection failed.';
}

if ($_POST && !$sError) {

    if ($_POST['password'] != FORM_PASS) {

        $sError = 'Wrong password.';

    } else {

        if (!empty($_POST['action']) && strtolower($_POST['action']) == 'delete') {

            //delete record
            if (!empty($_POST['id']) && is_numeric($_POST['id'])) {

                $sth = $oPDO->prepare('
                    DELETE
                    FROM gameswithgold
                    WHERE id = :id
                    LIMIT 1'
                );
                $sth->bindValue(':id', (int)$_POST['id']);

                if ($sth->execute()) {
                    $sSuccess = 'Game deleted from database.';
                    $aData = array();
                } else {
                    $sError = 'Failed to delete game from database.';
                    $aData = $_POST;
                }
            }

        } elseif (!empty($_POST['action']) && strtolower($_POST['action']) == 'save') {

            //validate stuff
            if (empty($_POST['game']) || empty($_POST['platform']) || empty($_POST['startdate']) || empty($_POST['enddate'])) {

                $sError = 'Missing fields.';

            } else {

                //everything ok
                if (!empty($_POST['id']) && is_numeric($_POST['id'])) {

                    //update query
                    $sth = $oPDO->prepare('
                        UPDATE gameswithgold
                        SET game = :game,
                            platform = :platform,
                            startdate = :startdate,
                            enddate = :enddate,
                            link = :link
                        WHERE id = :id
                        LIMIT 1'
                    );
                    $sth->bindValue(':game'     , $_POST['game']);
                    $sth->bindValue(':platform' , $_POST['platform']);
                    $sth->bindValue(':startdate', date('Y-m-d', strtotime($_POST['startdate'])));
                    $sth->bindValue(':enddate'  , date('Y-m-d', strtotime($_POST['enddate'])));
                    $sth->bindValue(':link'     , $_POST['link']);
                    $sth->bindValue(':id'       , (int)$_POST['id']);

                    if ($sth->execute()) {
                        $sSuccess = 'Game updated in database.';
                        $aData = array();
                    } else {
                        $sError = 'Failed to update game in database.';
                        $aData = $_POST;
                    }

                } else {

                    //insert query
                    $sth = $oPDO->prepare('
                        INSERT INTO gameswithgold (game, platform, startdate, enddate, link)
                        VALUES (:game, :platform, :startdate, :enddate, :link)'
                    );
                    $sth->bindValue(':game'     , $_POST['game']);
                    $sth->bindValue(':platform' , $_POST['platform']);
                    $sth->bindValue(':startdate', date('Y-m-d', strtotime($_POST['startdate'])));
                    $sth->bindValue(':enddate'  , date('Y-m-d', strtotime($_POST['enddate'])));
                    $sth->bindValue(':link'     , $_POST['link']);

                    if ($sth->execute()) {
                        $sSuccess = 'Game added to database.';
                        $aData = array();
                    } else {
                        $sError = 'Failed to add game to database.';
                        $aData = $_POST;
                    }
                }
            }
        }
    }

} elseif (!empty($_GET['id']) && is_numeric($_GET['id']) && !$sError) {
    
    //fetch record to edit
    $sth = $oPDO->prepare('
        SELECT *
        FROM gameswithgold
        WHERE id = :id
        LIMIT 1'
    );
    $sth->bindValue(':id', (int)$_GET['id']);
    if ($sth->execute()) {
        $aData = $sth->fetch(PDO::FETCH_ASSOC);
    }
/*} elseif (!empty($_GET['del']) && is_numeric($_GET['del']) && !$sError) {

    $sth = $oPDO->prepare('
        DELETE
        FROM gameswithgold
        WHERE id = :id
        LIMIT 1'
    );
    $sth->bindValue(':id', (int)$_GET['del']);
    if ($sth->execute()) {
        $sSuccess = 'Record deleted.';
    }*/
}

//fetch upcoming games
$aUpcomingGames = array();
$sth = $oPDO->prepare('
    SELECT *
    FROM gameswithgold
    WHERE enddate >= CURDATE()
    ORDER BY startdate, enddate, platform, game'
);
if ($sth->execute()) {
    $aUpcomingGames = $sth->fetchAll(PDO::FETCH_ASSOC);

    //TODO: don't hardcode this
    foreach ($aUpcomingGames as $key => $aGame) {
        if (in_array($aGame['platform'], array('Xbox 360', 'Xbox One'))) {
            $aUpcomingGames[$key]['platformclass'] = 'success';
        } elseif (in_array($aGame['platform'], array('Playstation 3', 'Playstation 4', 'Playstation Vita', 'Playstation (cross-buy)'))) {
            $aUpcomingGames[$key]['platformclass'] = 'info';
        }
    }
}

//array with platform possibilities
$aPlatforms = array(
    'Xbox 360',
    'Xbox One',
    'Playstation 3',
    'Playstation 4',
    'Playstation Vita',
    'Playstation (cross-buy)',
);

?>
<html>
    <head>
        <title>@XboxPSfreegames - add game</title>
        <link href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.2/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>

        <script type="text/javascript">
            $(function() {
                $('.confirm').on('click', function(e) {

                    if (!confirm('Are you sure you want to delete this record?')) {
                        e.preventDefault();
                        return false;
                    }
                });
            });
        </script>
    </head>
    <body>
        <div class="container">
            <h1 class="col-md-offset-2"><a href="https://twitter.com/XboxPSfreegames" target="_blank">@XboxPSfreegames</a> - <?= (empty($_GET['id']) ? 'add' : 'edit') ?> game</h1>
            <?php if (!empty($sSuccess)) { ?><div role="alert" class="alert alert-success"><?= $sSuccess ?></div><?php } ?>
            <?php if (!empty($sError)) { ?><div role="alert" class="alert alert-danger"><?= $sError ?></div><?php } ?>
            <form method="post" action="gameswithgoldadd.php" class="form-horizontal" role="form">

                <?php if (!empty($_GET['id'])) { ?>
                    <input type="hidden" name="id" value="<?= (int)$_GET['id'] ?>" />
                <?php } ?>
                <div class="form-group">
                    <label for="game" class="col-sm-2 control-label">Game</label>
                    <div class="col-sm-10">
                        <input type="text" id="game" name="game" class="form-control" value="<?= @$aData['game'] ?>" required />
                    </div>
                </div>
                <div class="form-group">

                    <label for="platform" class="col-sm-2 control-label">Platform</label>
                    <div class="col-sm-10">
                        <select name="platform" id="platform" class="form-control">
                        <?php foreach ($aPlatforms as $sPlatform) { ?>
                            <option value="<?= $sPlatform ?>" <?= (@$aData['platform'] == $sPlatform ? 'selected' : '') ?>><?= $sPlatform ?></option>
                        <?php } ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">

                    <label for="startdate" class="col-sm-2 control-label">Start date</label>
                    <div class="col-sm-10">
                        <input type="date" name="startdate" id="startdate" class="form-control" value="<?= (!empty($aData['startdate']) ? $aData['startdate'] : date('Y-m-d')) ?>" required/>
                    </div>
                </div>
                <div class="form-group">

                    <label for="enddate" class="col-sm-2 control-label">End date</label>
                    <div class="col-sm-10">
                        <input type="date" name="enddate" id="enddate" class="form-control" value="<?= (!empty($aData['enddate']) ? $aData['enddate'] : date('Y-m-d', strtotime('+2 week'))) ?>" required />
                    </div>
                </div>
                <div class="form-group">

                    <label for="link" class="col-sm-2 control-label">Link</label>
                    <div class="col-sm-10">
                        <input type="text" name="link" id="link" class="form-control" value="<?= (!empty($aData['link']) ? $aData['link'] : '') ?>" />
                    </div>
                </div>
                <div class="form-group">

                    <label class="col-sm-2 control-label">Majornelson</label>
                    <div class="col-sm-10">
                        <p class="form-control-static">
                            <a href="http://majornelson.com/category/games-with-gold/" target="_blank">http://majornelson.com/category/games-with-gold/</a>
                        </p>
                    </div>

                    <label class="col-sm-2 control-label">Wikipedia Xbox</label>
                    <div class="col-sm-10">
                        <p class="form-control-static">
                            <a href="http://en.wikipedia.org/wiki/List_of_Games_with_Gold_games" target="_blank">http://en.wikipedia.org/wiki/List_of_Games_with_Gold_games</a>
                        </p>
                    </div>
                </div>
                <div class="form-group">

                    <label class="col-sm-2 control-label">Playstation Plus blog</label>
                    <div class="col-sm-10">
                        <p class="form-control-static">
                            <a href="http://blog.us.playstation.com/tag/playstation-plus/" target="_blank">http://blog.us.playstation.com/tag/playstation-plus/</a>
                        </p>
                    </div>

                    <label class="col-sm-2 control-label">Wikipedia PS</label>
                    <div class="col-sm-10">
                        <p class="form-control-static">
                            <a href="http://en.wikipedia.org/wiki/List_of_Instant_Game_Collection_games_(North_America)" target="_blank">http://en.wikipedia.org/wiki/List_of_Instant_Game_Collection_games_(North_America)</a><br/>
                            <a href="http://en.wikipedia.org/wiki/List_of_Instant_Game_Collection_games_(PAL_region)" target="_blank">http://en.wikipedia.org/wiki/List_of_Instant_Game_Collection_games_(PAL_region)</a>
                        </p>
                    </div>
                </div>
                <div class="form-group">

                    <label for="password" class="col-sm-2 control-label">Password</label>
                    <div class="col-sm-10">
                        <input type="password" name="password" id="password" class="form-control" required />
                    </div>
                </div>
                <div class="form-group">

                    <label for="link" class="col-sm-2 control-label">&nbsp;</label>
                    <div class="col-sm-10">
                        <input type="submit" name="action" value="Save" class="btn btn-primary" />
                        <?php if (!empty($_GET['id'])) { ?>
                            <a href="gameswithgoldadd.php" class="btn btn-default">Cancel</a>
                            <input type="submit" name="action" value="Delete" class="btn btn-danger confirm" />
                        <?php } ?>
                    </div>
                </div>
            </form>

            <?php if ($aUpcomingGames) { ?>
                <table class="table table-condensed">
                    <caption>Upcoming free games</caption>
                    <tr>
                        <th>Game</th><th>Platform</th><th>Start date</th><th>End date</th><th></th>
                    </tr>
                    <?php foreach ($aUpcomingGames as $aGame) { ?>
                        <tr class="table-striped <?= $aGame['platformclass'] ?>">
                            <td><a href="<?= $aGame['link'] ?>" target="_blank"><?= $aGame['game'] ?></a></td>
                            <td><?= $aGame['platform'] ?></td>
                            <td><?= $aGame['startdate'] ?></td>
                            <td><?= $aGame['enddate'] ?></td>
                            <td><a href="?id=<?= $aGame['id']?>"><span class="glyphicon glyphicon-pencil"></span></a></td>
                        </tr>
                    <?php } ?>
                </table>
            <?php } ?>
        </div>
    </body>
</html>