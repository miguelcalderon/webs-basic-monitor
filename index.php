<?php
session_start();
$auth = false;
$usuarios = array(
    'admin' => 'admin'
);
if (!isset($_SESSION['auth'])) {
    if (isset($_POST["usuario"]) && isset($_POST["contrasena"])) {
        if (isset($usuarios[$_POST["usuario"]])) {
            if ($_POST["contrasena"] === $usuarios[$_POST["usuario"]]) {
                $auth = true;
                $_SESSION['auth'] = true;
            }
        }
    }
} else {
    $auth = true;
}
if (!$auth) {
?>
<!doctype html>
<html>
<head>
    <meta charset="UTF-8">
<title>Webs basic monitor</title>
<meta name="referrer" content="no-referrer">
</head>
<body>
<section>
 <form method="post">
 <input type="text" name="usuario" id="usuario" placeholder="User"><br>
 <input type="password" name="contrasena" id="contrasena" placeholder="Password"><br>
 <input type="submit" value="Log in">
 </form>
</section>
</body>
</html>
<?php
  exit();
} else {
    function printRow($url) {
		global $siteidx;
?>
        <li data-url="<?php echo $url; ?>"><form action="/api/v1/webs.php?action=delete" method="post" class="delete_site_button"><input type="hidden" name="url" value="<?php echo $url; ?>"><input type="submit" value="X"></form>&nbsp;<input type="checkbox" class="show_site" id="site_<?php echo $siteidx; ?>"><label for="site_<?php echo $siteidx; ?>"></label><a href="<?php echo $url; ?>" target="_blank"><?php echo $url; ?></a>&nbsp;<div class="site_status"></div>&nbsp;<div class="site_broken"></div></li>
        <?php
    }
	$siteidx = 0;
    $_SESSION['message'] = "";
    $settings = require_once('config.php');
    require_once('db.php');
    $db = new bd($settings['dbs'], $settings['usr'], $settings['pwd']);
    $webs_pro = $webs_dev = array();
    if (!$db->checkTable($settings['webs_table'], join(',', $settings['web_params']))) {
        $_SESSION['message'].= $db->msg;
    } else {
        $webs = $db->getArray($settings['webs_table']);
        if ($webs === false) {
            $_SESSION['message'].= $db->msg;
        } else {
            foreach ($webs as $web) {
                if ($web['branch'] === 'production') {
                    $webs_pro[] = $web['url'];
                } else {
                    if ($web['branch'] === 'develop') {
                        $webs_dev[] = $web['url'];
                    }
                }
            }
        }
    }
    ?>
<!doctype html>
<html>
<head>
    <meta charset="UTF-8">
<title>Panel de control SantaCC</title>
<meta name="referrer" content="no-referrer">
    <style>
        ul form { display: inline; }
        li .site_status {
            width: 16px;
            height: 16px;
            display: inline-block;
			vertical-align: middle;
            background-size: 100%;
            background-repeat: no-repeat;
            background-position: center;
        }
        li.loading .site_status {
            background-image: url('/images/loading.gif');
            transform: translateZ(0);
        }
        li.online .site_status {
            background-image: url('/images/online.png');
        }
        li.offline .site_status {
            background-image: url('/images/offline.png');
        }
        li.offline .site_status::after {
            content: attr(data-status);
            color: #ff0000;
            font-weight: bold;
            margin-left: 100%;
        }
        li .site_broken {
			font-weight: bold;
            display: inline-block;
			margin-left: 1.5em;
			cursor: pointer;
			vertical-align: bottom;
		}
		.show_site {
			position: absolute;
			left: -99999px;
		}
		.show_site + label {
			cursor: pointer;
		}
		.show_site + label::after {
			content: '►';
		}
		.show_site:checked + label::after {
			content: '▼';
		}
		.site_iframe_container {
			width: 320px;
			height: 256px;
			overflow: hidden;
			margin-top: 1em;
			margin-bottom: 2em;
			color: #ff0000;
			font-weight: bold;
		}
		.site_iframe {
			transform: scale(0.2);
			transform-origin: top left;
		}
    </style>
</head>
<body>
<h1>Webs</h1>
<?php
if (strlen($_SESSION['message']) > 0) {
    echo "<div>Message:<br>".$_SESSION['message']."</div>";
    unset($_SESSION['message']);
}
?>
Add site:
<form method="post" action="/api/v1/webs.php">
    <input type="text" name="url" id="url">
    <select name="branch" id="branch">
        <option value="production">Production</option>
        <option value="develop">Development</option>
    </select>
    <input type="submit" value="Send">
</form>
<h2>Production</h2>
<ul>
    <?php
    foreach ($webs_pro as $web_pro) {
        printRow($web_pro);
		$siteidx++;
    }
    ?>
</ul>
<h2>Development</h2>
<ul>
    <?php
    foreach ($webs_dev as $web_dev) {
        printRow($web_dev);
		$siteidx++;
    }
    ?>
</ul>
<textarea id="copyelement"></textarea>
<script src="//ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>
<script>
    'use strict';
    (function ($, window) {
		function systemNotification (title, text) {
			function stripHTML(HTMLtext) {
				var tmp = document.createElement("DIV");
				tmp.innerHTML = HTMLtext;
				return tmp.textContent || tmp.innerText || "";
			}

			function notifyMe() {
				var notification = new Notification(title, { body: stripHTML(text)/* , icon: '/images/notification_icon.png' */ });
				setTimeout(notification.close.bind(notification), 5000);
			}

			if ('Notification' in window) {
				if (Notification.permission === 'granted') {
					notifyMe();
				} else {
					if (Notification.permission !== 'denied') {
						Notification.requestPermission(function (permission) {
							if (permission === "granted") {
								notifyMe();
							}
						});
					}
				}
			}
		}
        function checkWebStatus(url) {
            var $site = $('li[data-url="' + url + '"]');
            if (!$site.hasClass('loading')) {
                setTimeout(function () {
                    $site.removeClass('online offline').addClass('loading').find('.site_status').removeAttr('data-status');
                }, 100);
                return $.get('/api/v1/webs.php?param=status&url=' + url)
                    .done(function () {
                        setTimeout(function () {
                            $site.removeClass('loading').addClass('online');
                            notified[url] = false;
                        }, 100);
                    })
                    .fail(function (jqXHR) {
						$site.removeClass('loading').addClass('offline').find('.site_status').attr('data-status', jqXHR.status);
						if (!notified[url]) {
							systemNotification(url + ' offline', url + ' offline');
							notified[url] = true;
						}
                    });
            }
        }
        function checkWebLinks(url) {
            var $site = $('li[data-url="' + url + '"]');
            //if (!$site.hasClass('loading')) {
            if (true) {
				/*
                setTimeout(function () {
                    $site.removeClass('online offline').addClass('loading').find('.site_status').removeAttr('data-status');
                }, 100);
				*/
                return $.get('/api/v1/webs.php?param=links&url=' + url)
                    .done(function (data) {
						//console.log('LINKS:', data);
						try {
							var brokenLinks = JSON.parse(data);
						} catch	(e) {
							return false;
						}
						if (brokenLinks.length > 0) {
							var pl = brokenLinks.length === 1 ? '' : 's',
							  brokenLinksShow = '';
							for (var i = 0, n = brokenLinks.length; i < n; i++) {
								brokenLinksShow+= brokenLinks[i].url + '\n';
							}
                            $site.find('.site_broken').html(brokenLinks.length + ' enlace' + pl + ' roto' + pl).attr('title', brokenLinksShow).css({ color: '#ff0000' });
						} else {
                            $site.find('.site_broken').html('✓').attr('title', 'No broken links.').css({ color: '#00ff00' });
						}
						/*
                        setTimeout(function () {
                            $site.removeClass('loading').addClass('online');
                            notified[url] = false;
                        }, 100);
						*/
                    })
                    .fail(function (jqXHR) {
						console.log('LINKS ERROR:', jqXHR.status);
                        $site.find('.site_broken').html('✗').attr('title', 'Could not check links: error ' + jqXHR.status).css({ color: '#ff0000' });
						/*
                        setTimeout(function () {
                            $site.removeClass('loading').addClass('offline').find('.site_status').attr('data-status', jqXHR.status);
                            if (!notified[url]) {
	                            systemNotification (url + ' offline', url + ' offline');
	                            notified[url] = true;
                            }
                        }, 100);
						*/
                    });
            }
        }
        function checkRandomWebStatus() {
            var $webs = $('li[data-url]'),
                webIndex = Math.floor((Math.random() * $webs.length));
            checkWebStatus($webs.eq(webIndex).attr('data-url'));
            setTimeout(checkRandomWebStatus, 10000);
        }
        function checkRandomWebLinks() {
            var $webs = $('li[data-url]'),
                webIndex = Math.floor((Math.random() * $webs.length));
            checkWebLinks($webs.eq(webIndex).attr('data-url'));
            setTimeout(checkRandomWebLinks, 30000);
        }
        var lanzador = 0,
        	notified = {};
		window.iframeNotAvailable = function (url) {
			$('li[data-url="' + url + '"]').find('.site_iframe_container').css({ height: 'auto' }).html('Web page preview not available.');
		};
        $('li[data-url]').each(function () {
            var siteUrl = $(this).attr('data-url');
            lanzador++;
            setTimeout(function () {
                checkWebStatus(siteUrl);
            }, lanzador * 2000);
        });
        $(document).on('click', '.site_broken', function (e) {
			$('#copyelement').val($(e.target).attr('title')).focus();
		});
        $(document).on('click', '.show_site', function (e) {
			var $site_container = $(e.target).closest('li'),
			  $site_frame = $site_container.find('.site_iframe_container');
			if ($site_frame.length === 0) {
				$('.site_iframe_container').remove();
				$site_container.append('<div class="site_iframe_container"><iframe width="1600" height="1280" src="' + $site_container.attr('data-url') + '" class="site_iframe" onerror="iframeNotAvailable(\'' + $site_container.attr('data-url') + '\');"></iframe></div>');
			} else {
				$site_frame.remove();
			}
		});
		$('.delete_site_button').on('submit', function (e) {
			if (confirm('Delete web site from the list?')) {
				return true;
			} else {
				return false;
			}
		});
        setTimeout(checkRandomWebStatus, 60000);
        setTimeout(checkRandomWebLinks, 30000);
    })(jQuery, window);
</script>
</body>
</html>
<?php
}
?>
