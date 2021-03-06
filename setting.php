<?php
/**
 * 设置保存页面
 */

require dirname(__FILE__).'/init.php';

if (ROLE != 'user' && ROLE != 'admin' && ROLE != 'vip') {
	msg('权限不足');
}

if (ROLE != 'admin' && stristr(strip_tags($_GET['mod']), 'admin:')) {
	msg('权限不足');
}

global $i;
global $m;

switch (SYSTEM_PAGE) {
	case 'admin:plugins:install':
		doAction('plugin_install_1');
		if (!class_exists('ZipArchive')) {
			msg('插件安装失败：你的主机不支持 ZipArchive 类，请返回');
		}
		if (!is_writable(SYSTEM_ROOT . '/plugins')) {
			msg('插件安装失败：你的主机不支持文件写入，请手动安装插件');
		}
		if (!isset($_FILES['plugin'])) {
			msg('若要安装插件，请上传插件包');
		}
		$file = $_FILES['plugin'];
		if (!empty($file['error'])) {
			msg('插件安装失败：在上传文件时发生错误：代码：' . $file['error']);
		}
		if (empty($file['size'])) {
			msg('插件安装失败：插件包大小无效 ( 0 Byte )，请确认压缩包是否已损坏');
		}
		$z    = new ZipArchive();
		if(!$z->open($file['tmp_name'])) {
			msg('插件安装失败：无法打开压缩包');
		}
		$rdc = explode('/', $z->getNameIndex(0), 2);
		$rd  = $rdc[0];
		if($z->getFromName($rd . '/' . $rd . '.php') === false) {
			msg('插件安装失败：插件包不合法，请确认此插件为'.SYSTEM_FN.'插件');
		}
		if(!$z->extractTo(SYSTEM_ROOT . '/plugins')) {
			msg('插件安装失败：解压缩失败');
		}
		doAction('plugin_install_2');
		msg('插件安装成功');
		break;

	case 'admin:plugins':
		doAction('plugin_setting_1');
		if (isset($_GET['dis'])) {
			inactivePlugin($_GET['dis']);
		}
		elseif (isset($_GET['act'])) {
			activePlugin($_GET['act']);
		}
		elseif (isset($_GET['uninst'])) {
			uninstallPlugin($_GET['uninst']);
		}
		doAction('plugin_setting_2');
		Redirect('index.php?mod=admin:plugins&ok');
		break;
	
	case 'admin:set':
		global $m;
		$sou = $_POST;
		if ($_GET['type'] == 'sign') {
			@option::set('cron_limit',$sou['cron_limit']);
			@option::set('tb_max',$sou['tb_max']);
			@option::set('sign_mode', serialize($sou['sign_mode']));
			@option::set('enable_addtieba',$sou['enable_addtieba']);
			@option::set('retry_max',$sou['retry_max']);
			@option::set('sign_hour',$sou['sign_hour']);
			@option::set('fb',$sou['fb']);
			@option::set('sign_sleep',$sou['sign_sleep']);
			if (empty($sou['fb_tables'])) {
				@option::set('fb_tables',NULL);
			} else {
				$fb_tables = explode("\n",$sou['fb_tables']);
				$fb_tab = array();
				$n= 0;
				foreach ($fb_tables as $value) {
					$n++;
					$value = strtolower($value);
					$sql = str_ireplace('{VAR-DB}', DB_NAME, str_ireplace('{VAR-TABLE}', trim(DB_PREFIX.$value), file_get_contents(SYSTEM_ROOT.'/setup/template.table.sql')));
					$m->query($sql);
					$fb_tab[$n] .= trim($value);
				}
				@option::set('fb_tables', serialize($fb_tab));
			}
		} else {
			@option::set('system_url',$sou['system_url']);
			@option::set('system_name',$sou['system_name']);
			@option::set('footer',$sou['footer']);
			@option::set('enable_reg',$sou['enable_reg']);
			@option::set('protect_reg',$sou['protect_reg']);
			@option::set('yr_reg',$sou['yr_reg']);
			@option::set('icp',$sou['icp']);
			@option::set('protector',$sou['protector']);
			@option::set('trigger',$sou['trigger']);
			@option::set('mail_mode',$sou['mail_mode']);
			@option::set('mail_name',$sou['mail_name']);
			@option::set('mail_yourname',$sou['mail_yourname']);
			@option::set('mail_host',$sou['mail_host']);
			@option::set('mail_port',$sou['mail_port']);
			@option::set('mail_auth',$sou['mail_auth']);
			@option::set('mail_smtpname',$sou['mail_smtpname']);
			@option::set('mail_smtppw',$sou['mail_smtppw']);
			@option::set('dev',$sou['dev']);
			@option::set('bduss_num',$sou['bduss_num']);
			@option::set('dev',$sou['dev']);
			@option::set('pwdmode',$sou['pwdmode']);
			@option::set('cron_pw',$sou['cron_pw']);
			@option::set('sign_multith',$sou['sign_multith']);
			@option::set('cktime',$sou['cktime']);
		}
		doAction('admin_set_save');
		Redirect('index.php?mod=admin:set:'. $_GET['type'].'&ok');
		break;

	case 'admin:tools':
		switch (strip_tags($_GET['setting'])) {
		case 'optim':
			global $m;
			$rs=$m->query("SHOW TABLES FROM `".DB_NAME.'`');
			while ($row = $m->fetch_row($rs)) {
				$m->query('OPTIMIZE TABLE  `'.DB_NAME.'`.`'.$row[0].'`');
		    }
			break;
		
		case 'fixdoing':
			option::set('cron_isdoing',0);
			break;

		case 'reftable':
			option::set('freetable',getfreetable());
			break;

		case 'cron_sign_again':
			option::set('cron_sign_again','');
			break;

		case 'runsql':
			global $m;
			if (!empty($_POST['sql'])) {
				$sql = str_ireplace('{VAR-DBNAME}', DB_NAME, str_ireplace('{VAR-PREFIX}', DB_PREFIX, $_POST['sql']));
				$m->xquery($sql);
			}
			break;

		case 'backup':
			global $m;
			$list  = !empty($_POST['tab']) ? array_map('addslashes', $_POST['tab']) : msg('请至少选择一个需要导出的表');
			$dump  = '#Warning: Do not change the comments!!!'  . "\n";
			$dump .= '#Tieba-Cloud-Sign Database Backup' . "\n";
			$dump .= '#Version:' . SYSTEM_VER . "\n";
			$dump .= '#Date:' . date('Y-m-d H:m:s') . "\n";
			$dump .= '############## Start ##############' . "\n";
			foreach ($list as $table) {
				$dump .= dataBak($table);
			}
			$dump .= "\n" . '############## End ##############';
			$file  = 'cloud_sign_' . date('Y-m-d_H-m-s') . '.sql';
			if (!empty($_POST['zip'])) {
				if (!is_dir(SYSTEM_ROOT.'/source/cache')) {
					mkdir(SYSTEM_ROOT.'/source/cache' , 0777 , true);
				}
				if(CreateZip($file , $dump , SYSTEM_ROOT.'/source/cache/' . $file . '.zip') === true) {
					header('Content-Type: application/zip');
					header('Content-Disposition: attachment; filename=' . $file . '.zip');
					echo file_get_contents(SYSTEM_ROOT.'/source/cache/' . $file . '.zip');
					unlink(SYSTEM_ROOT.'/source/cache/' . $file . '.zip');
					die;
				}
			}
			header('Content-Type: text/x-sql');
			header('Content-Disposition: attachment; filename=' . $file);
			echo $dump;
			die;
			break;

		case 'remtab':
			global $m;
			if (!empty($_POST['tab'])) {
				$m->query('DROP TABLE IF EXISTS `'.$_POST['tab'].'`');
			}
			break;

		case 'truntab':
			if (!empty($_POST['tab'])) {
				$m->query('TRUNCATE TABLE `'.$_POST['tab'].'`');
			}
			break;

		/*
		case 'updatefid':
			global $m;
			global $i;
			$fbs = $i['tabpart'];
				if (!isset($_GET['ok'])) {
				$step = $_GET['step'] + 30;
				$next = isset($_GET['in']) ? strip_tags($_GET['in']) : 'tieba';
				$c  = $m->once_fetch_array("SELECT COUNT(*) AS `x` FROM `".DB_PREFIX.$next."`");
				$c2 = $m->once_fetch_array("SELECT COUNT(*) AS `x` FROM `".DB_PREFIX.$next."` WHERE `fid` = '0'");
				if ($c2['x'] >= 1) {
					$x  = $m->query("SELECT * FROM `".DB_PREFIX.$next."` WHERE `fid` = '0' LIMIT 30");
					while ($r = $m->fetch_array($x)) {
						$fid = misc::getFid($r['tieba']);
						$m->query("UPDATE `".DB_PREFIX.$next."` SET  `fid` =  '{$fid}' WHERE `".DB_PREFIX.$next."`.`id` = {$r['id']};");
					}
				} else {
					$step = 0;
					if ($next == 'tieba') {
						$next = $fbs[1];
					} else {
						foreach ($fbs as $ke => $va) {
							if ($va == $next) {
								$newkey = $ke + 1;
								$next = $ke[$newkey];
								break;
							}
						}
					}
				}

				if (empty($next)) {
					msg('<meta http-equiv="refresh" content="0; url='.SYSTEM_URL.'index.php?mod=admin:tools&ok" />即将完成更新......程序将自动继续<br/><br/><a href="'.SYSTEM_URL.'index.php?mod=admin:tools&ok">如果你的浏览器没有自动跳转，请点击这里</a>',false);
				}

				msg('<meta http-equiv="refresh" content="0; url=setting.php?mod=updatefid&step='.$step.'&in='.$next.'" />已经更新表：'.DB_PREFIX.$next.' / 即将更新表：'.DB_PREFIX.$next.'<br/><br/>当前进度：'.$step.' / '.$c['x'].' <progress style="width:60%" value="'.$step.'" max="'.$c['x'].'"></progress><br/><br/>切勿关闭浏览器，程序将自动继续<br/><br/><a href="setting.php?mod=updatefid&step='.$step.'&in='.$next.'">如果你的浏览器没有自动跳转，请点击这里</a>',false);
				}
			break;
			*/

		default:
			msg('未定义操作');
			break;
		}
		doAction('admin_tools_doing');
		Redirect('index.php?mod=admin:tools&ok');
		break;

	case 'admin:users':
		switch (strip_tags($_POST['do'])) {
			case 'cookie':
				foreach ($_POST['user'] as $value) {
					$m->query("DELETE FROM `".DB_NAME."`.`".DB_PREFIX."baiduid` WHERE  `".DB_PREFIX."baiduid`.`uid` = ".$value);
				}
				doAction('admin_users_cookie');
				break;
			
			case 'clean':
				foreach ($_POST['user'] as $value) {
					CleanUser($value);
				}
				doAction('admin_users_clean');
				break;

			case 'delete':
				foreach ($_POST['user'] as $value) {
					DeleteUser($value);
				}
				doAction('admin_users_delete');
				break;

			case 'crole':
				foreach ($_POST['user'] as $value) {
					if ($_POST['crolev'] == 'user') {
						$role = 'user';
					} elseif ($_POST['crolev'] == 'admin') {
						$role = 'admin';
					} elseif ($_POST['crolev'] == 'vip') {
						$role = 'vip';
					} elseif ($_POST['crolev'] == 'banned') {
						$role = 'banned';
					} 

					$m->query("UPDATE `".DB_NAME."`.`".DB_PREFIX."users` SET `role` = '{$role}' WHERE `".DB_PREFIX."users`.`id` = {$value}");
				}
				doAction('admin_users_crole');
				break;

			case 'cset':
				foreach ($_POST['user'] as $value) {
					option::udel($value);
				}
				doAction('admin_users_cset');
				break;

			case 'add':
				$name = isset($_POST['name']) ? strip_tags($_POST['name']) : '';
				$mail = isset($_POST['mail']) ? strip_tags($_POST['mail']) : '';
				$pw   = isset($_POST['pwd']) ? strip_tags($_POST['pwd']) : '';
				$role = isset($_POST['role']) ? strip_tags($_POST['role']) : 'user';

				if (empty($name) || empty($mail) || empty($pw)) {
					msg('添加用户失败：请正确填写账户、密码或邮箱');
				}
				$x=$m->once_fetch_array("SELECT COUNT(*) AS total FROM `".DB_NAME."`.`".DB_PREFIX."users` WHERE name='{$name}'");
				if ($x['total'] > 0) {
					msg('添加用户失败：用户名已经存在');
				}
				$m->query('INSERT INTO `'.DB_NAME.'`.`'.DB_PREFIX.'users` (`id`, `name`, `pw`, `email`, `role`, `t`) VALUES (NULL, \''.$name.'\', \''.EncodePwd($pw).'\', \''.$mail.'\', \''.$role.'\', \''.getfreetable().'\');');
				doAction('admin_users_add');
				Redirect('index.php?mod=admin:users&ok');
				break;

			default:
				msg('未定义操作');
				break;
			}
		Redirect('index.php?mod=admin:users&ok');
		break;

	case 'admin:cron':
		doAction('cron_setting_1');
		if (!empty($_GET['act'])) {
			cron::aset($_GET['act'] , array('no' => 0));
		}
		elseif (!empty($_GET['dis'])) {
			cron::aset($_GET['dis'] , array('no' => 1));
		}
		elseif (isset($_GET['uninst'])) {
			cron::del($_GET['uninst']);
		}
		elseif (isset($_GET['add'])) {
			cron::set($_POST['name'], $_POST['file'], $_POST['no'], $_POST['status'], $_POST['freq'] ,$_POST['lastdo'], $_POST['log']);
		}
		elseif (isset($_GET['run'])) {
			$return = cron::run($_GET['file'], $_GET['run']);
			cron::aset($_GET['run'] , array('lastdo' => time() , 'log' => $return));
		}
		elseif (isset($_GET['xorder'])) {
			foreach ($_POST['order'] as $key => $value) {
				cron::aset($key , array('orde' => $value));
			}
		}
		doAction('cron_setting_2');
		Redirect('index.php?mod=admin:cron&ok');
		break;

	case 'admin:update:back':
		if (isset($_GET['del'])) {
			if (file_exists(SYSTEM_ROOT . '/setup/update_backup/' . $_GET['del'])) {
				DeleteFile(SYSTEM_ROOT . '/setup/update_backup/' . $_GET['del']);
			}
			Redirect('index.php?mod=admin:update:back&ok');
		}

		if (isset($_GET['dir'])) {
			if (file_exists(SYSTEM_ROOT . '/setup/update_backup/' . $_GET['dir'] . '/__backup.ini')) {
				if(CopyAll(SYSTEM_ROOT . '/setup/update_backup/' . $_GET['dir'] , SYSTEM_ROOT) !== true) {
					msg('版本回滚失败');
				}
				unlink(SYSTEM_ROOT . '/__backup.ini');
				msg('版本回滚成功','index.php');
			} else {
				msg('版本回滚失败：该备份不存在或不正确');
			}
		}
		break;

	case 'baiduid':
		if (isset($_GET['delete'])) {
			CleanUser(UID);
			$m->query("DELETE FROM `".DB_NAME."`.`".DB_PREFIX."baiduid` WHERE `".DB_PREFIX."baiduid`.`uid` = ".UID);
		}
		elseif (isset($_GET['bduss'])) {
			if (option::get('bduss_num') == '-1' && ROLE != 'admin') msg('本站禁止绑定新账号');

			if (option::get('bduss_num') != '0' && ISVIP == false) {
				$count = $m->once_fetch_array("SELECT COUNT(*) AS `c` FROM `".DB_NAME."`.`".DB_PREFIX."baiduid` WHERE `".DB_PREFIX."baiduid`.`uid` = ".UID);
				if (($count['c'] + 1) > option::get('bduss_num')) msg('您当前绑定的账号数已达到管理员设置的上限<br/><br/>您当前已绑定 '.$count['c'].' 个账号，最多只能绑定 '.option::get('bduss_num').' 个账号'); 
			}
			$m->query("INSERT INTO `".DB_NAME."`.`".DB_PREFIX."baiduid` (`uid`,`bduss`) VALUES  (".UID.", '{$_GET['bduss']}' )");
		}
		elseif (isset($_GET['del'])) {
			$del = (int) $_GET['del'];
			$x=$m->once_fetch_array("SELECT * FROM  `".DB_NAME."`.`".DB_PREFIX."users` WHERE  `id` = ".UID." LIMIT 1");
			$m->query("DELETE FROM `".DB_NAME."`.`".DB_PREFIX."baiduid` WHERE `".DB_PREFIX."baiduid`.`uid` = ".UID." AND `".DB_PREFIX."baiduid`.`id` = " . $del);	
			$m->query('DELETE FROM `'.DB_NAME.'`.`'.DB_PREFIX.$x['t'].'` WHERE `'.DB_PREFIX.$x['t'].'`.`uid` = '.UID.' AND `'.DB_PREFIX.$x['t'].'`.`pid` = '.$del);
		}
		doAction('baiduid_set');
		Redirect("index.php?mod=baiduid");
		break;

	case 'showtb':
		if (isset($_GET['set'])) {
			$x=$m->fetch_array($m->query('SELECT * FROM  `'.DB_NAME.'`.`'.DB_PREFIX.TABLE.'` WHERE  `uid` = '.UID.' LIMIT 1'));
			$f=$x['tieba'];
			foreach ($_POST['no'] as $k => $x) {
				$id = intval($k);
				if ($x == '0') {
					$xv = '0';
				} else {
					$xv = '1';
				}
				$m->query("UPDATE `".DB_PREFIX.TABLE."` SET `no` =  '{$xv}' WHERE  `id` = '{$id}' AND `uid` = '".UID."' ;");
			}
			Redirect('index.php?mod=showtb&ok');
		}
		elseif (isset($_GET['ref'])) {
			$r = misc::scanTiebaByUser();
			Redirect('index.php?mod=showtb');
		}
		elseif (isset($_GET['clean'])) {
			CleanUser(UID);
			Redirect('index.php?mod=showtb');
		}
		elseif (isset($_POST['add'])) {
			if (option::get('enable_addtieba') == '1') {
				$v = addslashes(htmlspecialchars($_POST['add']));
				$pid = (int) strip_tags($_POST['pid']);
				$osq = $m->query("SELECT * FROM `".DB_NAME."`.`".DB_PREFIX.TABLE."` WHERE `uid` = ".UID." AND `tieba` = '{$v}';");
				if($m->num_rows($osq) == 0) {
					$m->query("INSERT INTO `".DB_NAME."`.`".DB_PREFIX.TABLE."` (`id`, `pid`, `uid`, `tieba`, `no`, `lastdo`) VALUES (NULL, {$pid} ,'".UID."', '{$v}', 0, 0);");
				}
			}
			Redirect('index.php?mod=showtb&ok');
		}
		doAction('showtb_set');
		break;

		case 'set':
			/*
			受信任的设置项，如果插件要使用系统的API去储存设置，必须通过set_save1或set_save2挂载点挂载设置名
			具体挂载方法为：
			global $PostArray;
			$PostArray[] = '设置名';
			为了兼容旧版本，可以global以后检查一下是不是空变量，为空则为旧版本
			*/
			$PostArray = array(
				'face_img',
				'face_baiduid'
			);
			doAction('set_save1');
			$set = array();
			foreach ($PostArray as $value) {
				if (!isset($i['post'][$value])) {
					$i['post'][$value] = '';
				}
				@option::uset($value , $i['post'][$value]);
			}
			doAction('set_save2');
			Redirect('index.php?mod=set&ok');
			break;

	case 'testmail':
		$x = misc::mail(option::get('mail_name'), SYSTEM_FN.' V'.SYSTEM_VER.' - 邮件发送测试','这是一封关于 ' . SYSTEM_FN . ' 的测试邮件，如果你收到了此邮件，表示邮件系统可以正常工作<br/><br/>站点地址：' . SYSTEM_URL);
		if($x === true) {
			Redirect('index.php?mod=admin:set&mailtestok');
		} else {
			msg('邮件发送失败，发件日志：<br/>'.$x);
		}
		break;
}

if (ROLE == 'admin' && $i['mode'][0] == 'plugin') {
	option::pset($i['mode'][1] , $_POST);
	Redirect("index.php?mod=admin:setplug&plug={$i['mode'][1]}&ok");
}