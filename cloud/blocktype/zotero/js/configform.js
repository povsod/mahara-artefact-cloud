$j('#instconf_usebibbase_container input[type="checkbox"]').click(function() {
    if ($j('#instconf_usebibbase').attr('checked') == 'checked') {
		$j('#instconf_bibstyle_header').addClass('hidden');
        $j('#instconf_bibstyle_container').addClass('hidden');
        $j('#instconf_bibstyle').addClass('hidden');
	}
	else {
		$j('#instconf_bibstyle_header').removeClass('hidden');
        $j('#instconf_bibstyle_container').removeClass('hidden');
        $j('#instconf_bibstyle').removeClass('hidden');
	}
});
