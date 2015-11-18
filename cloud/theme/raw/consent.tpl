<html>
<head>
  <title>{str tag='servicename' section='blocktype.cloud/$service'} - Consent</title>
  <link rel="shortcut icon" href="{$WWWROOT}artefact/cloud/blocktype/{$service}/theme/raw/static/images/favicon.ico" />
  <style>
  body { font-family:Arial,"Nimbus Sans L",Helvetica,sans-serif; font-size:0.75em; }
  table, td, th, tr, thead, tbody, tfoot, colgroup, col { font-size:1em; text-align:right; }
  th { padding-right:10px; }
  .submit { font-weight:bold; }
  </style>
</head>

<body>
<center>
<div style="width:60%; text-align:left; padding:20px;">
<img src="{$WWWROOT}artefact/cloud/blocktype/{$service}/theme/raw/static/images/service.png" border="0">

<div style="padding:40px 25%">
<h2>{str tag='consenttitle' section='artefact.cloud' arg1=$sitename arg2=$servicename}</h2>
<p>{str tag='consentmessage' section='artefact.cloud'}</p>
{if $instructions}<p>{$instructions|safe}</p>{/if}
<center>
<div style="background-color:#eee; padding:20px">
{$form|safe}
</div>
</center>
</div>

</div>
</center>
</body>
</html>