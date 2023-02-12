<html>
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<link rel="shortcut icon" href="/PiRadio16.gif" />
	<link rel="stylesheet" href="styles.css">
	<title>PiRadio mini web interface - config page</title>
</head>
<body>
<?php
$piradio_version = str_replace("\n","",file_get_contents( "/usr/share/radio/version" ));
echo "<b>PiRadio v. ".$piradio_version."</b>";
?>
<div id="config"><a href="index.php">radio</a></div></br>
<hr>
<?php
$msg = $_GET['file'];
if (isset($msg)) {
	if ($msg == "restart") {
		$end = shell_exec('sudo ./scripts/restart.sh');
		echo "PiRadio restart in progress.<br>\r\n";
		echo "<script>\r\n";
		echo "// redirect to main after 1 second\r\n";
		echo "window.setTimeout(function() {\r\n";
		echo "  window.location.href = 'index.php';\r\n";
		echo "}, 1000);\r\n";
		echo "</script>\r\n";
	} elseif ($msg == "reboot") {
		$end = shell_exec('sudo ./scripts/reboot.sh');
		/* UWAGA! Folder z plikami uruchamianymi przez sudo
		musi byc dopisany w pliku /etc/sudoers     */
		echo "Reboot in progress.<br>\r\n";
		echo "Wait!<br>\r\n";
		echo "<script>\r\n";
		echo "// redirect to main after 30 seconds\r\n";
		echo "window.setTimeout(function() {\r\n";
		echo "  window.location.href = 'index.php';\r\n";
		echo "}, 30000);\r\n";
		echo "</script>\r\n";
	} elseif ($msg == "clear_caches") {
		$end = shell_exec('sudo ./scripts/clear_caches.sh');
		echo "Clear logs and caches.<br>\r\n";
		echo "Wait!<br>\r\n";
		echo "<script>\r\n";
		echo "// redirect to main after 1 second\r\n";
		echo "window.setTimeout(function() {\r\n";
		echo "  window.location.href = 'index.php';\r\n";
		echo "}, 1000);\r\n";
		echo "</script>\r\n";
	} elseif ($msg == "audio") {
		$selected = $_POST['output'];
		$hda = $_POST['hda'];
		$output_old = $_POST['output_old'];
		$hda_old = $_POST['hda_old'];
		if (isset($selected)) {
			if ( ($selected == $output_old) and ($hda == $hda_old) ) {
				echo "No changes in audio output configuration.<br>\r\n";
				echo "<script>\r\n";
				echo "// redirect to main after 2 seconds\r\n";
				echo "window.setTimeout(function() {\r\n";
				echo "  window.location.href = 'index.php';\r\n";
				echo "}, 2000);\r\n";
				echo "</script>\r\n";
			} else {
				$end = shell_exec('sudo ./scripts/get_audio_info.sh');
				$lines = explode(PHP_EOL, $end);
				if (count($lines) < 4 ) {
					echo "There is only ONE audio output in Your PiRadio!<br><br>";
					$selected = 'internal';
				}
				// Set default mixer and device names for internal audio
				$mixer = "PCM";
				$device = "Internal audio device";
				// Set default volume limits for internal audio (HQ)
				$volume_min = 35;
				$volume_max = 95;
				// Prepare pwm parameter
				$pwm = ( $hda == 'hdaudio') ? "2" : "1";
				if ( $pwm == '1') {
					// Default volume limits for internal audio (SQ)
					$volume_min = 40;
					$volume_max = 100;
				}
				// If external USB audio selected
				if ( $selected == 'usb' ) {
					// Default volume limit for USB device
					$volume_min = 0;
					$volume_max = 100;
					// Check number of USB card
					preg_match('/bcm2835/', $lines[0], $matches);
					$usbline = (isset($matches[0])) ? 2 : 0;
					// strange !!!
					preg_match("/\[(.*?)\]/", $lines[$usbline], $matches);
					$device = $matches[1];
					preg_match("/\'(.*?)\'/", $lines[$usbline + 1], $matches);
					$mixer = $matches[1];
				}
				echo "New audio device settings.<br>";
				echo "<br>Device: <b>".$device."</b><br>";
				echo "Mixer: <b>".$mixer."</b><br>";
				echo "Internal audio high quality mode: <b>";
				if ( $pwm == 2 ) {
					echo "Yes";
				} else {
					echo "No";
				}
				echo "</b><br>";
				$piradio = file_get_contents( "/etc/radiod.conf" );
				$piradio_new = preg_replace("/\nvolume_min *= *.*/", "\nvolume_min=".$volume_min, $piradio);
				$piradio_new = preg_replace("/\nvolume_max *= *.*/", "\nvolume_max=".$volume_max, $piradio_new);
				$piradio_array = parse_ini_string($piradio_new);
				$volume_min = ($piradio_array['volume_min']);
				$volume_max = ($piradio_array['volume_max']);
				echo "Volume limits:<br>";
				echo "<b>";
				echo "min: ".$volume_min."<br>";
				echo "max: ".$volume_max."</b><br>";
				file_put_contents('/etc/radiod.conf', $piradio_new);
				chmod("/etc/radiod.conf", 0755);
				echo "<br>";
				$end = shell_exec('sudo ./scripts/set_audio.sh '.$selected.' '.$mixer.' '.$pwm );
				echo "Reboot in progress.<br>\r\n";
				echo "Wait!<br>\r\n";
				echo "<script>\r\n";
				echo "// redirect to main after 30 seconds\r\n";
				echo "window.setTimeout(function() {\r\n";
				echo "  window.location.href = 'index.php';\r\n";
				echo "}, 30000);\r\n";
				echo "</script>\r\n";
			}
		} else {
			echo "Audio output device not selected.<br>\r\n";
			echo "<script>\r\n";
			echo "// redirect to main after 2 seconds\r\n";
			echo "window.setTimeout(function() {\r\n";
			echo "  window.location.href = 'index.php';\r\n";
			echo "}, 2000);\r\n";
			echo "</script>\r\n";
		}
	} elseif ($msg == "remote") {
		$selected = $_POST['remote'];
		if (isset($selected)) {
			$remote = file_get_contents( "/usr/share/radio/hardware/remotes/".$selected );
			preg_match('/\n# *brand: *.*/', $remote, $matches);
			$remotename = preg_replace("/\n# *brand: */", "", $matches[0]);
			echo "Selected <b>".$remotename."</b> remote controller.<br>\r\n";
			$end = shell_exec('sudo ./scripts/set_remote.sh '.$selected );
			echo "Reboot in progress.<br>\r\n";
			echo "Wait!<br>\r\n";
			echo "<script>\r\n";
			echo "// redirect to main after 30 seconds\r\n";
			echo "window.setTimeout(function() {\r\n";
			echo "  window.location.href = 'index.php';\r\n";
			echo "}, 30000);\r\n";
			echo "</script>\r\n";
		} else {
			echo "Remote controller not selected.<br>\r\n";
			echo "<script>\r\n";
			echo "// redirect to main after 2 seconds\r\n";
			echo "window.setTimeout(function() {\r\n";
			echo "  window.location.href = 'index.php';\r\n";
			echo "}, 2000);\r\n";
			echo "</script>\r\n";
		}
	} elseif ($msg == "stations") {
		$option = $_POST['submit'];
		if ($option == "new") {
			echo "<b>New stations list:</b>\r\n";
			$stations = $_POST['stations'];
			if ($stations[0] == "#") {
				$stations = "\r\n".$stations;
			}
			$stations_tmp = preg_replace("/\n#.*/", "", $stations);
			echo "<pre>".htmlspecialchars($stations_tmp)."</pre>";
			file_put_contents('/var/lib/radiod/stationlist_new', $stations);
			chmod("/var/lib/radiod/stationlist_new", 0755);
			echo "\r\n";
			echo "<form action='changeconf.php?file=".$msg."' method='post'>";
			echo "\r\n";
			echo '<button type="submit" name="submit" value="ok">Confirm new list</button>';
			echo "\r\n";
			echo '<button type="submit" name="submit" value="no">Cancel</button>';
		} elseif ($option == "old") {
			if (file_exists( "/var/lib/radiod/stationlist_old" )) {
				echo "<b>New stations list:</b>\r\n";
				$stations = file_get_contents( "/var/lib/radiod/stationlist_old" );
				if ($stations[0] == "#") {
					$stations = "\r\n".$stations;
				}
				$stations_tmp = preg_replace("/\n#.*/", "", $stations);
				echo "<pre>".htmlspecialchars($stations_tmp)."</pre>";
				file_put_contents('/var/lib/radiod/stationlist_new', $stations);
				chmod("/var/lib/radiod/stationlist_new", 0755);
				echo "\r\n";
				echo "<form action='changeconf.php?file=".$msg."' method='post'>";
				echo "\r\n";
				echo '<button type="submit" name="submit" value="ok">Restore this list</button>';
				echo "\r\n";
				echo '<button type="submit" name="submit" value="no">Cancel</button>';
			} else {
				echo "<b>No previous stations list.</b>\r\n";
				echo "<script>\r\n";
				echo "// redirect to main after 2 seconds\r\n";
				echo "window.setTimeout(function() {\r\n";
				echo "  window.location.href = 'index.php';\r\n";
				echo "}, 2000);\r\n";
				echo "</script>\r\n";
			}
		} elseif ($option == "ok") {
			echo "<b>Update stations list in progress.</b><br>\r\n";
			echo "Wait!<br>\r\n";
			echo "<script>\r\n";
			echo "// redirect to main after 2 seconds\r\n";
			echo "window.setTimeout(function() {\r\n";
			echo "  window.location.href = 'index.php';\r\n";
			echo "}, 2000);\r\n";
			echo "</script>\r\n";
			$end = shell_exec('sudo ./scripts/new_stationlist.sh');
		} elseif ($option == "no") {
			echo "<b>Stations list change canceled.</b>\r\n";
			echo "<script>\r\n";
			echo "// redirect to main after 2 seconds\r\n";
			echo "window.setTimeout(function() {\r\n";
			echo "  window.location.href = 'index.php';\r\n";
			echo "}, 2000);\r\n";
			echo "</script>\r\n";
		}
	} elseif ($msg == "network") {
		$option = $_POST['submit'];
		if ($option == "confirm") {
			$login = $_POST["user"];
			$password = $_POST["password"];
			$media_link = $_POST["media_link"];
			$media_link = str_replace('\\', '/', $media_link);
			if ($media_link == "") {
				$share_string = '';
			} else {
				if ($login == "") {
					$share_string = 'mount.cifs -o vers="2.0",ro "'.$media_link.'" /share';
				} else {
					$share_string = 'mount.cifs -o vers="2.0",user="'.$login.'",password="'.$password.'",ro "'.$media_link.'" /share';
				}
			}
			echo "New network media folder configuration.\r\n";
			echo "<pre>Network path: <b>".$media_link."</b>\r\n";
			echo "Login: <b>".$login."</b>\r\n";
			echo "Password: <b>".$password."</b>\r\n";
			echo "Shell command: <b>".$share_string."</b></pre>\r\n";
			echo "<form action='changeconf.php?file=".$msg."' method='post'>";
			echo "\r\n";
			echo "<input type='hidden' name='share_string' value='".$share_string."'><br>\r\n";
			echo "<button type='submit' name='submit' value='ok'>Confirm network folder config</button>";
			echo "\r\n";
			echo "<button type='submit' name='submit' value='no'>Cancel</button>";
			echo "\r\n";
		} elseif ($option == "ok") {
			echo "<b>Update network folder config.</b><br>\r\n";
			echo "Wait!<br>\r\n";
			echo "<script>\r\n";
			echo "// redirect to main after 2 seconds\r\n";
			echo "window.setTimeout(function() {\r\n";
			echo "  window.location.href = 'index.php';\r\n";
			echo "}, 2000);\r\n";
			echo "</script>\r\n";
			$share_string = $_POST["share_string"];
			file_put_contents('/var/lib/radiod/share', $share_string);
			chmod("/var/lib/radiod/share", 0755);
		} elseif ($option == "no") {
			echo "<b>Network folder config change canceled.</b>\r\n";
			echo "<script>\r\n";
			echo "// redirect to main after 2 seconds\r\n";
			echo "window.setTimeout(function() {\r\n";
			echo "  window.location.href = 'index.php';\r\n";
			echo "}, 2000);\r\n";
			echo "</script>\r\n";
		}
	} elseif ($msg == "update") {
		$confirmation = $_POST["submit"];
		if ($confirmation == "yes") {
			echo "Update from github in progress.<br>";
			$end = shell_exec('sudo ./scripts/make_tmp.sh');
			$end = shell_exec('sudo ./scripts/tmp_upd.sh');
			echo "Wait!<br>\r\n";
			echo "<script>\r\n";
			echo "// redirect to main after 30 seconds\r\n";
			echo "window.setTimeout(function() {\r\n";
			echo "  window.location.href = 'index.php';\r\n";
			echo "}, 30000);\r\n";
			echo "</script>\r\n";
		} else {
			echo "Update from github canceled.<br>\r\n";
			$end = shell_exec('sudo ./scripts/cancel_update.sh');
			echo "<script>\r\n";
			echo "// redirect to main after 2 seconds\r\n";
			echo "window.setTimeout(function() {\r\n";
			echo "  window.location.href = 'index.php';\r\n";
			echo "}, 2000);\r\n";
			echo "</script>\r\n";
		}
	} elseif ($msg == "rss") {
		$rss_link = $_POST["rss_link"];
		file_put_contents('/var/lib/radiod/rss', $rss_link);
		chmod("/var/lib/radiod/rss", 0755);
		$rss_link_new = file_get_contents( "/var/lib/radiod/rss" );
		echo "New RSS config:<br>";
		echo "<b>".$rss_link_new."</b>";
	} elseif ($msg == "radio") {
		$rss = (isset($_POST['rss'])) ? "rss=yes" : "rss=no";
		$bright = (isset($_POST['bright'])) ? "bright=yes" : "bright=no";
		$media_update = (isset($_POST['media_update'])) ? "media_update=yes" : "media_update=no";
		$pandora_available = (isset($_POST['pandora_available'])) ? "pandora_available=yes" : "pandora_available=no";
		$startup = $_POST['startup'];
		$startup_string = "startup=".$startup;
		$piradio = file_get_contents( "/etc/radiod.conf" );
		$piradio_new = preg_replace("/\nrss *= *.*/", "\n".$rss, $piradio);
		$piradio_new = preg_replace("/\nbright *= *.*/", "\n".$bright, $piradio_new);
		$piradio_new = preg_replace("/\nmedia_update *= *.*/", "\n".$media_update, $piradio_new);
		$piradio_new = preg_replace("/\npandora_available *= *.*/", "\n".$pandora_available, $piradio_new);
		$piradio_new = preg_replace("/\nstartup *= *.*/", "\n".$startup_string, $piradio_new);
		$piradio_array = parse_ini_string($piradio_new);
		$rss = ($piradio_array['rss']) ? "yes" : "no";
		$bright = ($piradio_array['bright']) ? "yes" : "no";
		$media_update = ($piradio_array['media_update']) ? "yes" : "no";
		$pandora_available = ($piradio_array['pandora_available']) ? "yes" : "no";
		echo "New Global PiRadio config:<br>";
		echo "<b>";
		echo "RSS in standby: ".$rss."<br>";
		echo "LCD high brightness: ".$bright."<br>";
		echo "Always update library: ".$media_update."<br>";
		echo "Pandora available: ".$pandora_available."<br>";
		echo "Startup source: ";
		switch ($startup) {
			case 'RADIO':
				echo "SHOUTcast radio";
				break;
			case 'MEDIA':
				echo "Media player";
				break;
			case 'PANDORA':
				echo "Pandora radio";
				break;
		}
		echo "<br>";
		file_put_contents('/etc/radiod.conf', $piradio_new);
		chmod("/etc/radiod.conf", 0755);
	} elseif ($msg == "pandora") {
		$login = 'user = '.$_POST["login"];
		$password = 'password = '.$_POST["password"];
		$proxy = 'control_proxy = '.$_POST["proxy"];
		/* Folder /home/pi/.config/ musi miec uprawnienia 755
		inaczej nie da sie stad odczytac plik w nim umieszczony */
		$pandora = file_get_contents( "/home/pi/.config/pianobar/config" );
		$pandora_new = preg_replace("/\nuser *= *.*/", "\n".$login, $pandora);
		$pandora_new = preg_replace("/\npassword *= *.*/", "\n".$password, $pandora_new);
		$pandora_new = preg_replace("/\ncontrol_proxy *= *.*/", "\n".$proxy, $pandora_new);
		$pandora_array = parse_ini_string($pandora_new);
		echo "New Pandora config:<br>";
		echo "<b>";
		echo "Login: ".$pandora_array['user']."<br>";
		echo "Password: ".$pandora_array['password']."<br>";
		echo "Proxy: ".$pandora_array['control_proxy']."</b><br>";
		file_put_contents('/home/pi/.config/pianobar/config', $pandora_new);
		chmod("/home/pi/.config/pianobar/config", 0755);
	} elseif ($msg == "rpi_update") {
		$option = $_POST['submit'];
		if ($option == "confirm") {
			echo "Raspberry Pi update.\r\n";
			echo "<pre><b>Warning!<br>";
			echo "This is Raspberry Pi system update.<br>";
			echo "The update procedure may take more than 10 minutes.<br>";
			echo "Do not turn off the PiRadio until a full restart.<br><br>";
			echo "If you want to track the process, cancel update now.<br>";
			echo "Login to your Raspberry pi via ssh and run command:<br><br>";
			echo "<i>sudo apt-get update && sudo apt-get -y dist-upgrade</i><br><br></b></pre>";
			echo "<form action='changeconf.php?file=".$msg."' method='post'>";
			echo "\r\n";
			echo "<input type='hidden' name='share_string' value='".$share_string."'><br>\r\n";
			echo "<button type='submit' name='submit' value='no'>Cancel Raspberry Pi update</button>";
			echo "\r\n";
			echo "<button type='submit' name='submit' value='ok'>Update</button>";
			echo "\r\n";
		} elseif ($option == "ok") {
			echo "<b>Raspberry Pi update.</b><br>\r\n";
			echo "Wait!<br>\r\n";
			$end = shell_exec('sudo ./scripts/rpi_system_update.sh');
			echo "<script>\r\n";
			echo "// redirect to main after 120 seconds\r\n";
			echo "window.setTimeout(function() {\r\n";
			echo "  window.location.href = 'index.php';\r\n";
			echo "}, 120000);\r\n";
			echo "</script>\r\n";
		} elseif ($option == "no") {
			echo "<b>Raspberry Pi update canceled.</b>\r\n";
			echo "<script>\r\n";
			echo "// redirect to main after 2 seconds\r\n";
			echo "window.setTimeout(function() {\r\n";
			echo "  window.location.href = 'index.php';\r\n";
			echo "}, 2000);\r\n";
			echo "</script>\r\n";
		}
	}
}
?>
<hr>
<a href="changeconf.php?file=restart"><button>PiRadio restart</button></a>
<a href="changeconf.php?file=reboot"><button>System reboot</button></a>
</body>
