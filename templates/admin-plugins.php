<?php if (!defined('SYSTEM_ROOT')) { die('Insufficient Permissions'); } if (ROLE != 'admin') { msg('权限不足！'); }

if (isset($_GET['ok'])) {
	echo '<div class="alert alert-success">插件操作成功</div>';
}

$x=getPlugins();
$plugins = '';
$stat=0;
foreach($x as $key=>$val) {
	$stat++;
	$pluginfo = '';
	if (!empty($val['Url'])) {
		$pluginfo .= '<b><a href="'.$val['Url'].'" target="_blank">'.$val['Name'].'</a></b>';
	} else {
		$pluginfo .= '<b>'.$val['Name'].'</b>';
	}
	if (!empty($val['Description'])) {
		$pluginfo .= '<br/>'.$val['Description'];
	} else {
		$pluginfo .= '<br/>';
	}

	if (!empty($val['Version'])) {
		$pluginfo .= '<br/>版本：'.$val['Version'];
	} else {
		$pluginfo .= '<br/>版本：1.0';
	}

	if (!empty($val['AuthorUrl'])) {
		$authinfo = '<a href="'.$val['AuthorUrl'].'" target="_blank">'.$val['Author'].'</a>';
	} else {
		$authinfo = $val['Author'];
	}

	if (!empty($val['For'])) {
		$fortc = '<br/>适用版本：'.$val['For'];
	} else {
		$fortc = '<br/>适用版本：不限';
	}

	if (in_array($val['Plugin'], unserialize(option::get('actived_plugins')))) {
		$status = '<font color="green">已激活</font> | <a href="setting.php?mod=admin:plugins&dis='.$val['Plugin'].'">禁用插件</a><br/>';
		if (file_exists(SYSTEM_ROOT.'/plugins/'.$val['Plugin'].'/'.$val['Plugin'].'_setting.php')) {
			$status .= '<a href="index.php?mod=admin:setplug&plug='.$val['Plugin'].'">打开插件设置</a>';
		}
	} else {
		$status = '<font color="black">已禁用</font> | <a href="setting.php?mod=admin:plugins&act='.$val['Plugin'].'">激活插件</a><br/>';
	}
	$plugins .= '<tr><td>'.$pluginfo.'</td><td>'.$authinfo.'<br/>'.$val['Plugin'].$fortc.'<td>'.$status.'<br/><a onclick="return confirm(\'你确实要卸载此插件吗？\');" href="setting.php?mod=admin:plugins&uninst='.$val['Plugin'].'" style="color:red;">卸载插件</a></td></tr>'; 
}

doAction('admin_plugins');
?>
<div class="alert alert-info" id="tb_num">当前有 <?php echo sizeof(unserialize(option::get('actived_plugins'))); ?> 个已激活的插件，总共有 <?php echo $stat ?> 个插件
<br/>插件手工安装方法：直接解包插件并上传到 /plugins/ 即可
<br/><a href="javascript:;" data-toggle="modal" data-target="#InstallPlugin">点击这里上传安装插件</a> | <a href="http://www.stus8.com/forum.php?mod=forumdisplay&fid=163&filter=sortid&sortid=13" target="_blank">插件商城</a>
</div>
<table class="table table-striped">
	<thead>
		<tr>
			<th style="width:40%">插件信息</th>
			<th style="width:30%">作者/标识符</th>
			<th style="width:30%">状态/操作</th>
		</tr>
	</thead>
	<tobdy>
		<?php echo $plugins; ?>
	</tbody>
</table>
<br/><br/><?php echo SYSTEM_FN ?> V<?php echo SYSTEM_VER ?> // 作者: <a href="http://zhizhe8.net" target="_blank">无名智者</a>

<div class="modal fade" id="InstallPlugin" tabindex="-1" role="dialog" aria-labelledby="InstallPluginLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 class="modal-title" id="myModalLabel">安装插件包</h4>
      </div>
      <form action="<?php echo SYSTEM_URL ?>setting.php?mod=admin:plugins:install" onsubmit="$('#installplugin_button').attr('disabled',true);" method="post" enctype="multipart/form-data">
      <div class="modal-body">
        请浏览插件包：( ZIP格式 )
        <br/><br/><input type="file" name="plugin" required accept="application/zip" style="width:100%">
        <br/><br/>您的主机必须支持写入才能安装插件，若不支持，请手工安装
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
        <button type="submit" class="btn btn-primary" id="installplugin_button">上传插件</button>
      </div>
      </form>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->