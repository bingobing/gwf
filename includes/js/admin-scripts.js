// JavaScript Document

jQuery(document).ready(function($) {
	
	// control --------------------------
	
	$('.pnq-gwf-select').chosen({
		disable_search_threshold: 20
	})
	
	// for font preview --------------------------
	
	$('.pnq-gwf-font-preview').hide();
	
	$('.pnq-gwf-font-list').change(function() {
		set_preview();
		$('.pnq-gwf-font-preview').fadeIn('fast');
	});
	
	$('.pnq-gwf-select-prev-text').change(function() {
		set_preview();
	});
	$('.pnq-gwf-select-font-size').change(function() {
		set_preview();	
	});
	
	function set_preview() {
		var family = $('.pnq-gwf-font-list option:selected').val();
		var text = $('.pnq-gwf-select-prev-text option:selected').text();	
		var size = $('.pnq-gwf-select-font-size option:selected').val();
		
		if(text == 'Font Name') {
			text = family;	
		}
		
		WebFont.load({
			google: {
				families: [family]
			},
			loading: function() {
				$('.pnq-gwf-preview-zone').css('visibility', 'hidden');	
			},
			active: function() {
				$('.pnq-gwf-preview-zone').css('visibility', 'visible').fadeIn('slow');	
			}
		});
		
		$('.pnq-gwf-preview-zone').html(text).css({
			'font-family': family,
			'font-size': size
		})
	}
	
	// tags --------------		
	var duration = 100;
	function select_all_headings() {
		$('.pnq-gwf-heading-tag li[class!="pnq-gwf-all-headings"]').switchClass('', 'on', duration);	
	}
	function deselect_all_headings() {
		$('.pnq-gwf-heading-tag li[class!="pnq-gwf-all-headings"]').switchClass('on', '', duration);	
	}

	$('.pnq-gwf-apply-list li').click(function() {
		$(this).toggleClass('on', duration);	
	});
	
	$('.pnq-gwf-all-headings').click(function() {
		var on = !$(this).hasClass('on');
		if(on) {
			select_all_headings();
		} else {
			deselect_all_headings();
		}	
	});
	
	$('.pnq-gwf-heading-tag li[class!="pnq-gwf-all-headings"]').click(function() {
		var on = true;
		if($(this).hasClass('on')) {
			on = false;	
		}
		if($('.pnq-gwf-heading-tag li[class!="pnq-gwf-all-headings"]').size() != $('.pnq-gwf-heading-tag li[class*="on"]').size() + 1) {
			on = false;	
		}
		
		if(on) {
			$('.pnq-gwf-all-headings').switchClass('', 'on', duration);
		} else {
			$('.pnq-gwf-all-headings').switchClass('on', '', duration);
		}
	});
	
	// font rules
	function reset_rules_form() {
		$('.pnq-gwf-apply-list li').switchClass('on', '', duration);
		$('#pnq_gwf_apply_classes').val('');
	}
	
	function valid_css_class(classes) {
		var clean = classes.split(/\s*,\s*/g);

		for( var i = clean.length-1; i >= 0; i-- ) {
			clean[i] = clean[i].replace(/\s[\s]+/g, '');
			//clean[i] = clean[i].replace(/[\s\W]+/g, '-');
			clean[i] = clean[i].replace(/^[\-]+/g, '');
			clean[i] = clean[i].replace(/[\-]+$/g, '');
			if(clean[i] == '') {
				clean.splice(i, 1);	
			}
		}
		//for(var i = 0; i < clean.length; i++) {
		//	clean[i] = '.'+clean[i];
		//}
		
		return clean;
	}
	
	$('#pnq_gwf_create_font_rule').click(function() {
		// gather infomation
		var family = $('#pnq_gwf_font_list').val();
		var affected = [];	
		var tags = $('.pnq-gwf-apply-list li[class*="on"]');
				
		// data validation
		if(family == '') {
			return $('#form_section_font').effect('highlight');
		}
		if(tags.size() < 1 && $('#pnq_gwf_apply_classes').val() == '') {
			return $('#form_section_affected').effect('highlight');
		}
		
		// proceed
		tags.each(function() {
            if($(this).hasClass('pnq-gwf-all-headings')) {
				return;
			};
			affected.push($(this).html());
        });
		if($('#pnq_gwf_apply_classes').val() != '') {
			var classes = $.trim($('#pnq_gwf_apply_classes').val());		
			classes = valid_css_class(classes);
			affected = $.merge(affected, classes);
		}
		
		
		var rule_htm = '<div class="pnq-gwf-font-rule clearfix">' + 
						   '<div><h4>Font:</h4><p class="pnq-gwf-rule-family">'+ family +'</p></div>' +
						   '<div><h4>Apply To:</h4><p class="pnq-gwf-rule-affected">'+ affected.join(',') +'</p></div>' +
						   '<a href="#" class="delete" title="delete"></a>' + 
					   '</div>';
		$(rule_htm).appendTo('.pnq-gwf-font-rules').effect('highlight');
		
		reset_rules_form();	
	});
	
	$('.pnq-gwf-font-rule .delete').click(function(e) {
		e.preventDefault();
		$(this).parent().slideUp( duration, '', function() {  
			$(this).remove();
		});
	});
	
	// prepare data
	$('#Update').click(function(e) {
		//e.preventDefault();
		var rules = '', rule = '';
		$('.pnq-gwf-font-rule').each(function() {
			var family = $(this).find('.pnq-gwf-rule-family').html();
			var affected = $(this).find('.pnq-gwf-rule-affected').html().split(',');
			
			var affected_json = '';
			for(var i = 0; i < affected.length; i++) {
				affected_json = affected_json + '"' + affected[i] + '",';	
			}
			affected_json = affected_json.substr(0, affected_json.length-1);
			rule = '{ "family" : "'+ family +'", "affected" : ['+ affected_json +'] },';
			rules = rules + rule;
		});
		rules = rules.substr(0, rules.length-1);
		rules = '{ "rules" : ['+ rules +'] }';
		$("<input type='hidden' name='pnq_gwf_font_rules' value='"+ rules +"' />").prependTo('#pnq-gwf-data-form');
		
		//$(this).submit();
	});
	
	// checkboxes
	$('input:checkbox').change(function() {
		if($(this).attr('checked') == 'checked' ) {
			$(this).val(1);
		} else {
			$(this).val(0);	
		}
	});
	
});