jQuery(document).ready(function() {
	jQuery("form.newsletter_manager").submit(function() {
		if ( !this.from.value.match(/^\S+@\S+\.\S+$/) ) // good enough
			return false;
		
		var ok = true;
		
		jQuery(this).find(":text").each(function() {
			var t = jQuery(this);
			if ( !t.val() || t.val() == t.attr('title') )
				ok = false;
		});
		
		if ( !ok )
			return false;
	});
	
	jQuery("form.newsletter_manager :text").focus(function() {
		var t = jQuery(this);
		if ( t.val() == t.attr('title') )
			t.val('');
	});
	
	jQuery("form.newsletter_manager :text").blur(function() {
		var t = jQuery(this);
		if ( t.val() == '' )
			t.val(t.attr('title'));
	});
});