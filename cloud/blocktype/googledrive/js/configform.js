$j('#instconf_size_container input[type="radio"][value="S"]').click(function () {
    $j('#instconf_width').val('480');
    $j('#instconf_height').val('360');
});
$j('#instconf_size_container input[type="radio"][value="M"]').click(function () {
    $j('#instconf_width').val('960');
    $j('#instconf_height').val('720');
});
$j('#instconf_size_container input[type="radio"][value="L"]').click(function () {
    $j('#instconf_width').val('1440');
    $j('#instconf_height').val('1080');
});
$j('#instconf_size_container input[type="radio"][value="C"]').click(function () {
    $j('#instconf_width').val('');
    $j('#instconf_height').val('');
});