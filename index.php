<!DOCTYPE html>
<html>
<head>
<style>
body {
  background: url("background.jpg");
  background-repeat: repeat-y;
  background-size: cover;
  color: white;
  margin: 0;
  padding: 0;
  left: 0;
  right: 0;
  position: absolute;
  font-size: 16px;
  text-align: center;
  font-family: "Lucida Console", Monaco, monospace;
}
form {
  border: 0;
  margin: 0;
}
ul {
  list-style-type: none;
  margin: 0;
  padding: 0;
  overflow: hidden;
}
li {
  float: left;
  display: block;
}
summary {
  background: rgba(255, 0, 0, .1);
  text-align: left;;
  font-size: 18px;
}
table {
  max-width: 100%;
  border-spacing: 0;
  text-align: left;
  font-size: 16px;
}
tr {
  max-width: 100%;
}
th, td {
  height: 100%;
  padding: 10px;
  overflow-x: hidden;
  vertical-align: middle;
}
tr:nth-child(even) {
  background-color: rgba(255, 255, 255, 0.50);
}
tr:nth-child(odd) {
  background-color: rgba(255, 255, 255, 0.25);
}
input {
  border: 0;
  box-sizing: border-box;
  color: white;
  text-indent: 0px;
  font-size: 16px;
  background: rgba(0, 0, 0, 0);
  font-family: "Lucida Console", Monaco, monospace;
  width: 100%;
}
</style>
	<title>PHP GroupMe Bot</title>
</head>
<body>
<?php
header('Content-type: text/html; charset=utf-8');
ini_set('display_errors', 1);
ini_set('session.save_path', getcwd());
error_reporting(-1);
include 'functions.php';
session_start();
if (file_exists('db.sqlite')) {
	if (isset($_SESSION['username'])) {
		if (isset($_POST['logout'])) {
			session_destroy();
			header("Refresh:1");
		}
		if (!empty($_POST['add_admin_name']) && !empty($_POST['add_admin_pass'])) {
			add_admin($_POST['add_admin_name'], $_POST['add_admin_pass']);
		}
		if (isset($_POST['delete_admin'])) {
			delete_admin($_POST['delete_admin']);
		}
		if (isset($_POST['change_admin_pass'])) {
			change_admin_pass($_POST['change_admin_pass']);
		}
		if (isset($_POST['config'])) {
			update_config($_POST['config']);
		}
		if (isset($_POST['find']) && isset($_POST['respond']) && !empty($_POST['find']) && !empty($_POST['respond'])) {
			add_response($_POST['find'], $_POST['respond']);
		}
		if (isset($_POST['delete'])) {
			del_responses($_POST['delete']);
		}
		if (isset($_POST['users'])) {
			if (isset($_POST['admins'])) {
				update_admins($_POST['admins']);
			} else {
				delete_admins();
			}
			if (isset($_POST['ignored'])) {
				update_ignored($_POST['ignored']);
			} else {
				delete_ignored();
			}
		}
		if (isset($_POST['settings'])) {
			update_settings($_POST['settings']);
		}
		if (isset($_POST['new_setting']) && !empty($_POST['new_setting'])) {
			add_setting($_POST['new_setting']);
		}
		if (isset($_POST['del_settings']) && !empty($_POST['del_settings'])) {
			del_settings($_POST['del_settings']);
		}
		if (isset($_POST['send']) && !empty($_POST['send'])) {
			send($_POST['send']);
		}?>
<div style="align: right; height: 5vh; background: rgba(0, 0, 0, .5);">
<ul>
<?php
$username = $_SESSION['username'];
echo "<li>$username Logged in</li>";
?>
<form name="logout" method="post" action="">
	<li><input type="hidden" name="logout" value="logout"></li>
	<input style="float: right; width: unset;" type="submit" value="Log Out">
</form>
</div>
<div style="overflow-y: scroll; height: 90vh">
<details>
<summary>Panel</summary>
<form name="panel" method="post" action="">
<table align="center">
	<tr>
		<th>Panel Admins</th>
		<th>Delete</th>
		<th>Change Password</th>
	</tr>
	<?php
		$admins = get_panel_admins();
		foreach ($admins as $element) {
			$name = $element;
			echo "<tr>";
			echo "<td>$name</td>";
			echo "<td><input type=\"checkbox\" name=\"delete_admin[]\" value=\"$name\"></td>";
			echo "<td><input type=\"password\" name=\"change_admin_pass[$name]\" placeholder=\"Password\"></td>";
			echo "</tr>";
		}?>
	<tr>
		<td><input type="text" name="add_admin_name" placeholder="Username"></td>
		<td colspan="2"><input type="password" name="add_admin_pass" placeholder="Password"></td>
	<tr>
		<th colspan="3"><input type="submit" value="Update"></th>
	</tr>
</table>
</form>
</details>
<details>
<summary>Config</summary>
<form name="config" method="post" action="">
<table align="center">
	<tr>
		<th>Setting</th>
		<th>Value</th>
		<th>New Value</th>
	</tr>
	<?php
		$config = get_config();
		foreach ($config as $element) {
			$name = $element['name'];
			$value = $element['value'];
			echo "<tr>";
			echo "<td>$name</td>";
			if (stripos($name, 'token') !== FALSE) {
				$value = str_repeat('*', strlen($value) - 4) . substr($value, -4);
				echo "<td>$value</td>";
				echo "<td><input type=\"text\" name=\"config[$name]\" value=\"$value\"></td>";
			} else {
				echo "<td>$value</td>";
				echo "<td><input type=\"text\" name=\"config[$name]\" value=\"$value\"></td>";
			}
			echo "</tr>";
		}?>
	<tr>
		<th colspan="3"><input type="submit" value="Update"></th>
	</tr>
</table>
</form>
</details>
<details>
<summary>Add</summary>
<form name="add" method="post" action="">
<h3>%n can be used to mention someone in a response</h3>
<table align="center">
	<tr>
		<th><input type="text" name="find" placeholder="Text to find"></th>
		<th><input type="text" name="respond" placeholder="Text to respond with"></th>
		<th><input type="submit" value="Add"></th>
	</tr>
</table>
</form>
</details>
<details>
<summary>Delete</summary>
<form name="delete" method="post" action="">
<table align="center">
	<tr>
		<th>Find</th>
		<th>Respond</th>
		<th>Delete</th>
	</tr>
	<?php
		$responses = get_responses();
		foreach ($responses as $element) {
			$find = $element['find'];
			$respond = $element['respond'];
			echo "<tr>";
			echo "<td>$find</td>";
			echo "<td>$respond</td>";
			echo "<td><input type=\"checkbox\" name=\"delete[]\" value=\"$find\"></td>";
			echo "</tr>";
		}?>
	<tr>
		<th colspan="3"><input type="submit" value="Remove"></th>
	</tr>
</table>
</form>
</details>
<details>
<summary>Users</summary>
<form name="Users" method="post" action="">
<table align="center">
	<tr>
		<th>Name</th>
		<th>Admin</th>
		<th>Ignored</th>
	</tr>
	<?php
		$admins = get_admins();
		$ignored = get_ignored();
		$users = get_users();
		$i = 0;
		foreach ($users as $user) {
			$name = htmlspecialchars($user["name"]);
			$userid = htmlspecialchars($user["userid"]);
			$avatar = $user["avatar"];
			echo "<tr>";
			echo "<td style=\"text-align: left;\"><img src=\"$avatar\" style=\"width:50px; height:50px; vertical-align: middle;\">$name ($userid)</td>";
			if (in_array($users[$i]['userid'], $admins)) {
				echo "<td><input type=\"checkbox\" name=\"admins[]\" value=\"$userid\" checked></td>";
			} else {
				echo "<td><input type=\"checkbox\" name=\"admins[]\" value=\"$userid\"></td>";
			}
			if (in_array($users[$i]['userid'], $ignored)) {
				echo "<td><input type=\"checkbox\" name=\"ignored[]\" value=\"$userid\" checked></td>";
			} else {
				echo "<td><input type=\"checkbox\" name=\"ignored[]\" value=\"$userid\"></td>";
			}
			echo "</tr>";
			$i++;
		}?>
	<tr>
		<th colspan="3"><input type="submit" value="Update"></th>
	</tr>
</table>
	<input type="hidden" name="users[]" value="1">
</form>
</details>
<details>
<summary>Settings</summary>
<form name="settings" method="post" action="">
<table align="center">
	<tr>
		<th>Name</th>
		<th>State</th>
		<th>Delete</th>
	</tr>
	<?php
		$settings = get_settings();
		foreach ($settings as $element=>$key) {
			$name = $element;
			$value = $key;
			echo "<tr>";
			echo "<td>$name</td>";
			if ($value) {
				echo "<td><input type=\"checkbox\" name=\"settings[]\" value=\"$name\" checked></td>";
			} else {
				echo "<td><input type=\"checkbox\" name=\"settings[]\" value=\"$name\"></td>";
			}
			echo "<td><input type=\"checkbox\" name=\"del_settings[]\" value=\"$name\"></td>";
			echo "</tr>";
		}?>
	<tr>
		<td>Add setting</td>
		<td colspan="2"><input type="text" name="new_setting" placeholder="Name for new setting"></td>
	</tr>
	<tr>
		<th colspan="3"><input type="submit" value="Update"></th>
	</tr>
</table>
	<input type="hidden" name="settings[]" value="1">
</form>
</details>
<details>
<summary>Log</summary>
<table style="width: 100%;">
<?php
		$log = get_log();
		foreach ($log as $element) {
			$timestamp = date("Y-m-d@H:i:s", $element['timestamp']);
			$entry = htmlspecialchars($element['entry']);
			echo "<tr>";
			echo "<td>$timestamp</td>";
			echo "<td>$entry</td>";
			echo "</tr>";
		}?>
</table>
</details>
</div>
<form name="send" method="post" action="">
<table style="width: 100%; position: fixed; bottom: 0; height: 5vh">
	<tr>
		<th><input type="text" name="send" placeholder="Message to send"></th>
	</tr>
</table>
		<input type="submit" value="Send" style="display: none">
</form>
<?php
	} else {
		disp_login();
		if (isset($_POST['username']) && isset($_POST['password'])) {
			$db = new PDO('sqlite:db.sqlite');
			$username = strtolower($_POST['username']);
			$password = $_POST['password'];
			$query = $db->prepare('SELECT password FROM auth WHERE username=:username');
			$query->bindValue(':username', $username, PDO::PARAM_STR);
			$query->execute();
			$hashed = $query->fetch(PDO::FETCH_COLUMN, 0);
			if (password_verify($password, $hashed)) {
				echo "Logging in...";
				$_SESSION['username'] = $username;
				header("Refresh:1");
			} else {
				echo "Incorrect password!";
			}
		}
	}
} else if (is_writeable('./')) {
	if (!empty($_POST) && initdb()) {
		$db = new PDO('sqlite:db.sqlite');
		$config = ['apitoken', 'bottoken', 'wutoken', 'wuloc'];
		$settings = ['litecoin', 'bitcoin', 'ethereum'];
		foreach($config as $variable) {
			$statement = $db->prepare('INSERT INTO config (name, value) VALUES (:name, :value)');
			$statement->bindValue(':name', $variable, PDO::PARAM_STR);
			$statement->bindValue(':value', $_POST[$variable], PDO::PARAM_STR);
			$statement->execute();
		}
		if ($_POST['log']) {
			$db->exec("INSERT INTO config (name, value) VALUES ('log', '1')");
		} else {
			$db->exec("INSERT INTO config (name, value) VALUES ('log', '1')");
		}
		if ((isset($_POST['wutoken'])) && isset($_POST['wuloc'])) {
			$db->exec("INSERT INTO settings (name, value) VALUES ('weather', '1')");
		} else {
			$db->exec("INSERT INTO settings (name, value) VALUES ('weather', '0')");
		}
		$db->exec("INSERT INTO settings (name, value) VALUES ('lights', '0')");
		$db->exec("INSERT INTO responses (find, respond) VALUES ('test', 'It works!')");
		add_admin($_POST['user'], $_POST['pass']);
		foreach($settings as $variable) {
			$statement = $db->prepare('INSERT INTO settings (name, value) VALUES (:name, :value)');
			$statement->bindValue(':name', $variable, PDO::PARAM_STR);
			$statement->bindValue(':value', '1', PDO::PARAM_STR);
			$statement->execute();
		}
		file_put_contents('.htaccess', "<Files \"db.sqlite\">\nDeny From All\n</Files>\n<Files \"sess*\">\nDeny From All\n</Files>");
		header("Refresh:1");
	}
disp_setup();
} else {
	echo "Working directory is not writeable, either chown it to the webserver user and group or allow write permissions to everyone (insecure!)";
}?>
</body>
</html>
