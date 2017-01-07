<!DOCTYPE HTML>
<html lang="zh">
  <head>
    <title>樂屋網 特休計算機 BETA</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <!-- Bootstrap core CSS -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
  </head>
  <body>

    <div class="container">
<h1>樂屋網 特休計算機 <sup class="text-muted">beta</sup></h1>
<hr>
<div>
  <form class="form" method="POST" action="">
    <div class="form-group">
      <label for="startDate">到職日：</label>
      <input type="date" id="startDate" name="startDate" placeholder="2015/01/01" value="<?=isset($_POST['startDate'])?$_POST['startDate']:""?>" max="2016-12-31" autofocus required>
    </div>
    <div class="form-group">
      <label for="suspendDays">育嬰假/留職停薪：</label>
      <input type="number" id="suspendDays" name="suspendDays" placeholder="0" min="0" value="<?=isset($_POST['suspendDays'])?$_POST['suspendDays']:"0"?>" required> 天
    </div>
    <button class="btn btn-primary" type="submit">算特休</button>
  </form>
</div>
<hr><br>
<?php
if(isset($_POST['startDate'])) {
  // 載入計算機
  require("annual_leaves_calc.php");
  
  // 建立計算機
  try {
    $alcalc = new annual_leaves_calc($_POST['startDate'], $_POST['suspendDays']);
    $startDate = $_POST['startDate'];
  } catch (DateErrorException $e) {
?>
<div class="panel panel-danger">
  <div class="panel-heading">錯誤！</div>
  <div class="panel-body">
    哈囉！您輸入的日期有問題喔！ :)
  </div>
</div>
<?php
  } 
  
  if(!isset($e)) {
  
  // 到職日必須在 2016/12/31 之前
  if( strtotime('2016/12/31 UTC') < strtotime($_POST['startDate']." UTC")  ) {
?>
<div class="panel panel-warning">
  <div class="panel-heading">提示</div>
  <div class="panel-body">
    2017 年起到職的同事沒有新舊制的問題，請直接依照新制計算即可。 :)
  </div>
</div>
<?php
  } else {
  
  // 算年資級距
  $sen_step = $alcalc->get_seniority('2016/12/31');
  if( $sen_step >= 1)
    $sen_step = floor( $sen_step );
  else if ( $sen_step >= 0.5 )
    $sen_step = 0.5;
  else
    $sen_step = 0;
    
  // 今年的計算分界日，以及分界日前一天。
  $line_date = '2017'.gmdate("/m/d",strtotime("$startDate UTC"));
  $line_date_y = '2017'.gmdate("/m/d",strtotime("$startDate UTC -1 day"));
  
  // 取得 2017 首個不完整週期的天數
  $first_cycle_days = round( (strtotime("$line_date UTC") - strtotime('2017/01/01 UTC'))/86400 );
  
  // 取得特休和福利假數
  $leaves1 = $alcalc->get_leaves('2017/01/01');
  $annual = ceil( ( $leaves1['annual'] * 8 ) / 365 * $first_cycle_days );
  $annual_formatted = array(
    'days' => floor($annual/8),
    'hours' => $annual % 8
  );
  
  $welfare = ceil( ( $leaves1['welfare'] * 8 ) / 365 * $first_cycle_days );
  $welfare_formatted = array(
    'days' => floor($welfare/8),
    'hours' => $welfare % 8
  );
  
  $total = $annual + $welfare;
  $total_formatted = array(
    'days' => floor($total/8),
    'hours' => $total % 8
  );
  
  // 取得下一個年度特休和福利假數
  $leaves2 = $alcalc->get_leaves($line_date);
  
  $next_annual = $leaves2['annual'];
  $next_welfare = $leaves2['welfare'];
  
  $next_total = $next_annual+$next_welfare;
?>
<div class="panel panel-info">
  <div class="panel-heading">計算結果</div>
  <div class="panel-body">
    您到<strong> <u>2016/12/31 為止</u>，年資級距</strong>為： <?php echo $sen_step;?> 年。<br /><br />
    您 <strong>2017/01/01 至 <?php echo $line_date_y; ?></strong> 期間的：<br />
    <strong>特休假</strong>為 <u><?php echo $annual_formatted['days']; ?> 天 <?php echo $annual_formatted['hours']; ?> 小時</u>（也就是 <?php echo $annual; ?> 小時）。<br />
    <strong>福利假</strong>為 <u><?php echo $welfare_formatted['days']; ?> 天 <?php echo $welfare_formatted['hours']; ?> 小時</u>（也就是 <?php echo $welfare; ?> 小時）。<br />
    加起來一共是 <u><?php echo $total_formatted['days']; ?> 天 <?php echo $total_formatted['hours']; ?> 小時</u>（也就是 <?php echo $total; ?> 小時）。<br /><br />
    您<strong>從 <?php echo $line_date ?> 起的一年期間</strong>的：<br />
    <strong>特休假</strong>為 <u><?php echo $next_annual; ?> 天</u>（也就是 <?php echo $next_annual*8; ?> 小時）。<br />
    <strong>福利假</strong>為 <u><?php echo $next_welfare; ?> 天</u>（也就是 <?php echo $next_welfare*8; ?> 小時）。<br />
    加起來一共是 <u><?php echo $next_total; ?> 天</u>（也就是 <?php echo $next_total*8; ?> 小時）。
  </div>
</div>

<?php

  if($sen_step < 1) {
?>
<div class="panel panel-warning">
  <div class="panel-heading">提醒您</div>
  <div class="panel-body">
    您的年資未滿一年，由於未滿一年年資的計算方式有待商榷，故結果可能不準確。
  </div>
</div>
<?php
  }
  }
  }
}
?>
</div> <!-- /container -->
  </body>
</html>
