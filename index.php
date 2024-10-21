<?php
// 设置上传和删除的密码
$uploadPassword = '$2y$10$T8530qBqMf29uYqziAcEguERX5QaF0mpJWGm9Cy7/ib0nt2xnpy8O';

// 获取当前目录
$baseDir = __DIR__;
$currentDir = isset($_GET['dir']) ? realpath($baseDir . '/' . $_GET['dir']) : $baseDir;

// 确保安全，防止路径遍历攻击
if (strpos($currentDir, $baseDir) !== 0) {
    die('非法操作！⛔️');
}

// 删除文件或文件夹
if (isset($_POST['delete'])) {
    if (password_verify($_POST['password'], $uploadPassword)) {
        $deletePath = realpath($currentDir . '/' . $_POST['delete']);
        if (strpos($deletePath, $baseDir) === 0) { // 确保删除操作在根目录内
            if (is_dir($deletePath)) {
                rmdir($deletePath) ? $message = "文件夹删除成功！✔️" : $message = "文件夹删除失败！👻";
            } elseif (is_file($deletePath)) {
                unlink($deletePath) ? $message = "文件删除成功！✔️" : $message = "文件删除失败！👻";
            }
        } else {
            $message = "非法删除操作！⛔️";
        }
    } else {
        $message = "密码错误，无法删除文件或文件夹！⛔️";
    }
}

// 上传文件处理逻辑
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    if (password_verify($_POST['password'], $uploadPassword)) {
        $uploadSuccess = true;
        $message = '';

        // 定义禁止上传的文件列表
        $forbiddenFiles = ['index.php', 'index.html', 'index.htm'];

        // 循环处理每一个上传的文件
        foreach ($_FILES['file']['name'] as $key => $fileName) {
            if ($_FILES['file']['error'][$key] == 0) {
                // 检查文件名是否在禁止列表中
                if (in_array(strtolower($fileName), $forbiddenFiles)) {
                    $message .= "文件 " . $fileName . " 被禁止上传！❌\n";
                    $uploadSuccess = false;
                    continue; // 跳过当前文件，继续处理下一个文件
                }

                // 处理文件名重名问题
                $targetPath = $currentDir . '/' . basename($fileName);
                $fileInfo = pathinfo($targetPath);
                $baseName = $fileInfo['filename'];
                $extension = isset($fileInfo['extension']) ? '.' . $fileInfo['extension'] : '';

                // 检查文件是否存在，如果存在则添加数字后缀
                $i = 1;
                while (file_exists($targetPath)) {
                    $targetPath = $currentDir . '/' . $baseName . '_' . $i . $extension;
                    $i++;
                }

                // 尝试将文件移动到目标目录
                if (move_uploaded_file($_FILES['file']['tmp_name'][$key], $targetPath)) {
                    $message .= "文件 " . basename($targetPath) . " 上传成功！✔️\n";
                } else {
                    $message .= "文件 " . $fileName . " 上传失败！👻\n";
                    $uploadSuccess = false;
                }
            } else {
                $message .= "文件 " . $fileName . " 上传时出错！❌\n";
                $uploadSuccess = false;
            }
        }

        if ($uploadSuccess) {
            $message .= "所有文件上传成功！💯";
        } else {
            $message .= "部分文件上传失败！⚠️";
        }
    } else {
        $message = "密码错误，无法上传文件！⛔️";
    }
}


// 新建文件夹
if (isset($_POST['new_folder'])) {
    if (password_verify($_POST['password'], $uploadPassword)) {
        $newFolder = $currentDir . '/' . basename($_POST['new_folder']);
        if (!file_exists($newFolder)) {
            mkdir($newFolder) ? $message = "文件夹创建成功！✔️" : $message = "文件夹创建失败！❌";
        } else {
            $message = "文件夹已存在！⚠️";
        }
    } else {
        $message = "密码错误，无法创建文件夹！⛔️";
    }
}

// 随机密码生成函数
function generateRandomPassword($length = 12)
{
    return substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, $length);
}

// 处理生成随机密码并加密
if (isset($_POST['generate_password'])) {
    $randomPassword = generateRandomPassword(); // 生成随机密码
    $hashedPassword = password_hash($randomPassword, PASSWORD_BCRYPT); // 使用 bcrypt 加密随机密码
    $genpwd_message = "<p>随机密码: <br /><p class='xzkd'>" . htmlspecialchars($randomPassword) . "</p></p>";
    $genpwd_message .= "<p>加密密码: <br /><p class='xzkd'>" . htmlspecialchars($hashedPassword) . "</p></p>";
}

// 处理自定义密码加密
if (isset($_POST['hash_custom_password'])) {
    if (isset($_POST['custom_password'])) {
        $customPassword = $_POST['custom_password'];
        $hashedPassword = password_hash($customPassword, PASSWORD_BCRYPT); // 使用 bcrypt 加密用户自定义密码
        $genpwd_message = "<p>自定义密码加密成功!</p>";
        $genpwd_message .= "<p>加密后的密码:  <br /><p class='xzkd'>" . htmlspecialchars($hashedPassword) . "</p></p>";
    }
}

// 处理密码验证
if (isset($_POST['verify_password_submit'])) {
    if (isset($_POST['verify_password']) && isset($_POST['hashed_password'])) {
        $verifyPassword = $_POST['verify_password']; // 用户输入的密码
        $hashedPassword = $_POST['hashed_password']; // 用户输入的加密密码

        // 验证密码是否匹配
        if (password_verify($verifyPassword, $hashedPassword)) {
            $genpwd_message = "<p>密码验证成功!</p>";
        } else {
            $genpwd_message = "<p>密码验证失败!</p>";
        }
    }
}

// 列出目录下所有文件和文件夹
function listDirectory($dir, $baseDir)
{
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item == '.' || $item == '..' || $item == 'index.php') continue;

        $path = realpath($dir . '/' . $item);
        $relativePath = str_replace($baseDir . '/', '', $path); // 获取相对路径

        if (is_dir($path)) {
            echo '<li><span>[文件夹]</span> <strong><a href="?dir=' . urlencode($relativePath) . '">' . '/' . htmlspecialchars($item) . '</a></strong> 
            <form style="display:inline;" method="post" action="">
                <input type="hidden" name="delete" value="' . htmlspecialchars($item) . '">
                <input type="hidden" name="password" class="passwordField">
                <button type="submit">删除</button>
            </form></li>';
        } else {
            // 使用 rawurlencode 对文件路径进行编码，确保特殊字符被正确处理
            $encodedPath = rawurlencode($relativePath);
            $fileName = htmlspecialchars($item); // 保留文件名的原始显示

            // 获取文件大小并将其转换为 MB
            $fileSizeBytes = filesize($path);
            $fileSizeMB = $fileSizeBytes / (1024 * 1024);
            $formattedSize = number_format($fileSizeMB, 2) . ' MB'; // 格式化文件大小到两位小数

            echo '<li><span>[文件]</span> <strong><a class="fileName" href="' . $encodedPath . '" download="' . $fileName . '">' . $fileName . '</a></strong> 
            <span> (' . $formattedSize . ')</span>
            <form style="display:inline;" method="post" action="">
                <input type="hidden" name="delete" value="' . htmlspecialchars($item) . '">
                <input type="hidden" name="password" class="passwordField">
                <button type="submit">删除</button>
            </form></li>';
        }
    }
}

function displayPasswordModification()
{
    global $uploadPassword;

    // 判断是否有提交的密码，并验证它是否正确
    if (isset($_POST['password']) && password_verify($_POST['password'], $uploadPassword)) {
        // 如果密码验证通过，且用户提交了新密码和确认密码
        if (isset($_POST['new_password'], $_POST['confirm_password']) && 
            !empty($_POST['new_password']) && 
            !empty($_POST['confirm_password'])) {

            // 检查新密码和确认密码是否一致
            if ($_POST['new_password'] === $_POST['confirm_password']) {
                // 对新密码进行哈希
                $newPasswordHash = password_hash($_POST['new_password'], PASSWORD_BCRYPT);

                // 调用函数更新密码哈希
                updatePasswordInFile($newPasswordHash);

                // 显示成功消息
                echo '<p>密码已更新成功！</p>';
            } else {
                // 显示密码不匹配错误信息
                echo '<p style="color: red;">新密码和确认密码不匹配，请重试。</p>';
            }
        } else {
            // 显示输入新密码的表单
            echo '
                <form method="POST">
                    <label for="new_password">输入新密码：</label>
                    <input type="password" name="new_password" id="new_password" required><br>
                    <label for="confirm_password">确认新密码：</label>
                    <input type="password" name="confirm_password" id="confirm_password" required><br>
                    <input type="hidden" name="password" class="passwordField" value="' . htmlspecialchars($_POST['password']) . '">
                    <button type="submit">更新密码</button>
                </form>
            ';
        }
    } else {
        echo '<p>请输入正确的当前密码以更改密码。</p>';
    }
}

function updatePasswordInFile($newPasswordHash)
{
    $filePath = __DIR__ . '/index.php'; // 当前的 index.php 路径
    $tempFilePath = __DIR__ . '/index_temp.php'; // 临时文件路径

    // 读取当前文件内容
    $fileContent = file_get_contents($filePath);

    // 构造旧的 $uploadPassword 赋值语句匹配
    $startString = "\$uploadPassword = '"; // 开始部分
    $endString = "';"; // 结束部分，只找到赋值结束，不包含注释

    // 查找并替换旧的哈希密码
    $startPos = strpos($fileContent, $startString);
    $endPos = strpos($fileContent, $endString, $startPos);

    if ($startPos !== false && $endPos !== false) {
        // 获取到旧的密码哈希部分
        $oldPasswordHash = substr($fileContent, $startPos + strlen($startString), $endPos - ($startPos + strlen($startString)));

        // 新的完整赋值语句
        $newAssignment = $startString . $newPasswordHash . $endString;

        // 使用 str_replace 来替换
        $updatedContent = str_replace($startString . $oldPasswordHash . $endString, $newAssignment, $fileContent);

        // 将修改后的内容写入临时文件
        file_put_contents($tempFilePath, $updatedContent);

        // 原子性地用临时文件替换当前文件
        rename($tempFilePath, $filePath); // 使用 rename 替换文件

        echo "密码更新成功！";
    } else {
        echo "未能找到匹配的密码字段。";
    }
}

?>

<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>zFile_PHP</title>
    <script>
		document.addEventListener('contextmenu', function(event) {
			// 检查是否在 <a> 标签或其子元素上右键单击
			if (event.target.closest('a')) {
				return; // 如果是 <a> 标签或其子元素，则退出函数，不阻止默认行为
			}

			event.preventDefault(); // 禁止默认的右键菜单
		});
        // Base64 编码函数
        function base64Encode(str) {
            return btoa(str); // 将字符串编码为Base64
        }

        // Base64 解码函数
        function base64Decode(str) {
            return atob(str); // 将Base64解码为字符串
        }

        // 存储密码，使用Base64编码保存密码
        function savePassword() {
            const password = document.getElementById('globalPassword').value;
            const encodedPassword = base64Encode(password); // Base64 编码密码
            localStorage.setItem('password', encodedPassword); // 保存编码后的密码
            applyPasswordToForms(password); // 把密码应用到表单
        }

        // 页面加载时解码并应用密码
        function loadPassword() {
            const encodedPassword = localStorage.getItem('password');
            if (encodedPassword) {
                const decodedPassword = base64Decode(encodedPassword); // 解码 Base64
                document.getElementById('globalPassword').value = decodedPassword;
                applyPasswordToForms(decodedPassword); // 将解码后的密码应用到表单
            }
        }

        // 清除密码
        function clearPassword() {
            localStorage.removeItem('password');
            document.getElementById('globalPassword').value = '';
            applyPasswordToForms('');
        }

        // 将密码应用到表单
        function applyPasswordToForms(password) {
            const passwordFields = document.querySelectorAll('.passwordField');
            passwordFields.forEach(field => {
                field.value = password;
            });
        }

        // 页面加载时执行密码加载
        window.onload = loadPassword;


        function openModal() {
            document.getElementById('pwdModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('pwdModal').style.display = 'none';
        }

        window.onclick = function(event) {
            const modal = document.getElementById('pwdModal');
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
    <style>
        body {
            user-select: none;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            background: linear-gradient(to top, #d9e1e6, white);
			background-attachment: fixed;
            height: 100vh;
			min-width: 600px;
        }

        .form-container {
            display: flex;
            align-items: center;
            /* 垂直居中对齐 */
            gap: 10px;
            /* 元素之间的间距 */
        }

        h1 {
            font-size: 1.6em;
            margin: 2px 0;
            color: darkslategrey;
        }

        h2 {
            font-size: 1.1em;
            margin: 2px 0;
            color: darkslategrey;
        }

        h4 {
            font-size: 0.8em;
            margin: 2px 0;
            color: gray;
        }

        a {
            text-decoration: none;
            color: #222;
        }

        a:hover,
        a:visited,
        a:active {
            color: #222;
        }

        button {
            padding: 4px 6px;
            cursor: pointer;
            background-color: #aaaaaa;
            color: #fff;
            border: none;
            border-radius: 4px;
        }

        button:hover {
            background-color: #218838;
        }

		ul {
			list-style-type: none;
			padding: 0;
			width: 100%;
			max-width: 1600px;
			column-count: 1; /* 默认一栏 */
			column-gap: 20px;
		}

		@media (min-width: 1100px) { /* 大屏幕且内容高度较大时，变成两栏 */
			ul {
				column-count: 2; /* 视口宽度大于1100px时，变成两栏 */
			}
		}

		@media (max-width: 1100px) { /* 手机端，宽度小于1100px时，永远是一栏 */
			ul {
				column-count: 1;
			}
		}
		
        li {
            margin: 2px 0;
            padding: 5px;
            display: flex;
            justify-content: space-between;
            background: linear-gradient(to right, #ccc, #fff, #ccc);
            align-items: center;
			word-wrap: break-word;
			min-width: 520px;
        }

        li span {
            color: darkslategrey;
        }

        li strong {
            font-weight: bold;
        }

        li:hover {
            background: linear-gradient(to right, #aab, #ccf, #aab);
        }

        input[type="password"],
        input[type="text"],
        input[type="file"] {
            padding: 4px;
            margin: 4px 0;
            width: 16ch;
            max-width: 300px;
            font-size: 1em;
            border: 1px solid #ccc;
        }

        input[type="file"] {
            width: 20ch;
        }

		p,
        pre {
            color: #D22;
            padding: 0;
            margin: 0;
			font-size: 1.2em;
			background-color: #DDD;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 100;
            top: 0;
            left: 50%;
            width: 100vw;
            height: 100vh;
            transform: translate(-50%, 0%);
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            left: 50%;
            transform: translate(-50%, 0%);
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            max-width: 400px;
            position: relative;
        }

        .close {
            position: absolute;
            top: 10px;
            right: 20px;
            font-size: 24px;
            cursor: pointer;
        }

        .xzkd {
            user-select: text;
            max-width: 290px;
            /* 设置最大宽度为290px */
            word-wrap: break-word;
            /* 如果单词太长，允许单词换行 */
            white-space: normal;
            /* 确保文本正常换行 */
        }

        footer {
            color: #888;
            font-size: 12px;
            text-align: center;
        }

		.fileName {
			min-width: 250px;
			color: #66A;
			white-space: nowrap; /* 禁止换行 */
			overflow: hidden;    /* 超出部分隐藏 */
			text-overflow: ellipsis; /* 超出部分显示省略号 */
			display: inline-block; /* 确保 ellipsis 生效 */
			max-width: 400px; /* 设置最大宽度，根据需要调整 */
		}
		
		#topHead{
			min-width: 400px;
			width:400px;
			background: linear-gradient(to top, #d9e1e6, white);
			padding-left: 3%;
		}
		
		.returnDir{
			background-color: #EA3;
			width:120px;
		}
    </style>
</head>

<body>
	<div id="topHead">
		<h1>🌼zFile_PHP🌼</h1>
		<h4>PHP单文件管理器</h4>

		<button onclick="openModal()">打开密码工具</button>

		<div class="form-container">
			<label for="globalPassword">密码:</label>
			<input type="password" id="globalPassword" placeholder="默认是:admin">
			<button onclick="savePassword()">保存</button>
			<button onclick="clearPassword()">清除</button>
		</div>

		<div class="function-container">

			<form action="?dir=<?php echo isset($_GET['dir']) ? urlencode($_GET['dir']) : ''; ?>" method="post">
				<label for="globalPassword">新建文件夹:</label>
				<input type="text" name="new_folder" required placeholder="文件夹名称">
				<input type="hidden" name="password" class="passwordField">
				<button type="submit">创建</button>
			</form>

			<form id="uploadForm" action="?dir=<?php echo isset($_GET['dir']) ? urlencode($_GET['dir']) : ''; ?>" method="post" enctype="multipart/form-data">
				<label for="globalPassword">上传文件:</label>
				<input type="hidden" name="password" class="passwordField">
				<input type="file" name="file[]" id="fileInput" multiple required>
				<button type="submit">上传</button>
			</form>
		</div>
		<h2>目录列表</h2>
		<?php if (isset($message)): ?>
			<pre><?php echo htmlspecialchars($message); ?></pre>
		<?php endif; ?>
		<?php if ($currentDir !== $baseDir): ?>
			<p class="returnDir"><a href="?dir=<?php echo urlencode(dirname(str_replace($baseDir, '', $currentDir))); ?>">返回上级目录</a></p>
		<?php endif; ?>
	</div>
	
    <ul>
        <?php listDirectory($currentDir, $baseDir); ?>
    </ul>



    <div id="pwdModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>密码工具</h2>
            <?php if (isset($genpwd_message)): ?>
                <div><?php echo $genpwd_message; ?></div>
            <?php endif; ?>
            <form method="POST">
                <button type="submit" name="generate_password">随机密码</button>
            </form>

            <form method="POST">
                <h3>自定义加密</h3>
                <label for="custom_password">密码:</label>
                <input type="password" id="custom_password" name="custom_password" required>
                <button type="submit" name="hash_custom_password">加密</button>
            </form>

            <form method="POST">
                <h3>验证密码</h3>
                <label for="hashed_password">密文:</label>
                <input type="text" id="hashed_password" name="hashed_password" required>
                <br>
                <label for="verify_password">明文:</label>
                <input type="password" id="verify_password" name="verify_password" required>

                <button type="submit" name="verify_password_submit">验证</button>
            </form>
            <?php displayPasswordModification(); ?>
        </div>
    </div>

    <footer>
        <span>Copyright @ HBH combination</span>
    </footer>

</body>

</html>