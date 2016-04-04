<html>
<head>
  <title>{str tag='servicename' section='blocktype.cloud/$SERVICE'} - Consent</title>
  {if $SUBSERVICE}
  <link rel="shortcut icon" href="{$WWWROOT}artefact/cloud/blocktype/{$SERVICE}/theme/raw/static/{$SUBSERVICE}/favicon.png" />
  {else}
  <link rel="shortcut icon" href="{$WWWROOT}artefact/cloud/blocktype/{$SERVICE}/theme/raw/static/images/favicon.png" />
  {/if}
  <link rel="stylesheet" type="text/css" href="{theme_url filename="style/style.css"}">
</head>

<body style="background-color:#fff">

<div class="row">

<div class="col-md-4" align="center">
    {if $SUBSERVICE}
    <img src="{$WWWROOT}artefact/cloud/blocktype/{$SERVICE}/theme/raw/static/{$SUBSERVICE}/service.png" border="0">
    {else}
    <img src="{$WWWROOT}artefact/cloud/blocktype/{$SERVICE}/theme/raw/static/images/service.png" border="0">
    {/if}
</div>
<div class="col-md-4 login-panel">
    <div>
        <h2>{str tag='consenttitle' section='artefact.cloud' arg1=$sitename arg2=$servicename}</h2>
        <p align="justify">{str tag='consentmessage' section='artefact.cloud'}</p>
        {if $instructions}<p align="justify">{$instructions|safe}</p>{/if}
        <br />
    </div>
    <div class="panel panel-default">
        <h3 class="panel-heading">
            {str tag="login"}
        </h3>
        <div class="panel-body">
            <noscript><p>{str tag="javascriptnotenabled"}</p></noscript>
            {dynamic}{insert_messages placement='loginbox'}{/dynamic}
            <div id="loginform_container">
                {$form|safe}
            </div>
        </div>
    </div>
</div>
<div class="col-md-4"></div>

</div>

</body>
</html>