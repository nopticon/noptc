var EE = {OK: '~[200]'}

function emptyFunction() { }

function el(e)
{
	if ($.isString(e))
	{
		e = $('#' + e);
	}
	
	return e;
}

$.extend({
	d: {
		v: [],
		store: function(k, v) { $.d.v[k] = v; },
		read: function(k) { return $.d.v[k]; }
	}
});

$(function() {
	$('.editor').wymeditor({
		lang: 'es'
	});
});

var _ = {
	len: function(a) {
		try { return a.length; } catch(q) { return 0; }
	},
	el: function(f, i) {
		return f.elements[i];
	},
	e: function(e) {
		try { el = e.target; } catch(ce) { el = $(e); }
		return el;
	},
	ga: function(el, k) {
		return $('#' + el).attr(k);
	},
	sa: function(el, k, v) {
		return $(el).attr(k, v);
	},
	isset: function(a) {
		return !$.isUndefined(a);
	},
	empty: function(e) {
		return (e == '' || !e);
	},
	split: function(s, a) {
		if (_.empty(a)) a = '|';
		
		return s.split(a);
	},
	glue: function(s, g) {
		a = '';
		$.each(s, function(z, i){
			a += ((i) ? g : '') + z;
		});
		return a;
	},
	replacement: function(s, a, b) {
		return s.replace(a, b);
	},
	fill: function(v, s) {
		return (v) ? v : s;
	},
	forceArray: function(a) {
		if (!$.isArray(a)) a = [];
		
		return a;
	},
	add: function(a, v) {
		a.push(v);
		return;
	},
	encode: function(s) {
		if (!$.isString(s)) {
			eval("s = '" + s + "'");
		}
		return encodeURIComponent($.trim(s));
	},
	call: function(url, callback, arg, rtype) {
		if (!url) return false;
		
		if (!rtype) rtype = 'text';
		
		eval('argd = {ghost: 1' + ((arg != '') ? ', ' + arg : '') + '};');
		return $.post(url, argd, callback, rtype);
	},
	fp: function(a, b) {
		var arg = { };
		$.each(a, function(k, v) {
			arg[k] = ($(b[v])) ? _.encode($F(b[v])) : '';
		});
		
		return $.param(arg);
	},
	v: function(el, v, a) {
		if (el = $(el)) {
			if (a && !_.empty($F(el))) {
				v = $F(el) + a + v;
			}
			
			el.value = v;
		}
		return false;
	},
	go: function(u) {
		if (_.h(u, 'Location'))
		{
			u = u.substr(10);
		}
		window.location = u;
		return;
	},
	reload: function() {
		window.location.reload();
		return false;
	},
	print: function() {
		window.print();
	},
	click: function(el)
	{
		$(el).click();
		return false;
	},
	clear: function(e) {
		$(e).html('');
		
		try { $('#search-box').show(); } catch(ce) { }
		
		return;
	},
	observe: function(d, e) {
		$($.el(d)).click(function () {
			_.clear(e);
		});
		return;
	},
	display: function(el) {
		return $($.el(el)).css('display');
	},
	code: function(el) {
		return $($.el(el)).html();
	},
	stripTags: function(string) {
		return string.replace(/(<([^>]+)>)/ig, '');
	},
	low: function(s) {
		return s.toLowerCase();
	},
	h: function(a, b) {
		return (a.indexOf(b) != -1);
	},
	entity_decode: function(s) {
		var el = document.createElement('textarea');
		el.innerHTML = s;
		return el.value;
	},
	_focus: function(a) {
		try { $($.el(a)).focus();
		} catch(ce) { }
		return false;
	},
	_efocus: function(a) {
		_.v(a, '');
		_._focus(a);
	},
	_toggle: function(a, b) {
		$('#' + a).hide();
		$('#' + b).show();
	},
	focus: function(el, sf) {
		var list = Form.getElements(el);
		first = false;
		end_sf = _.len(sf);
		ins = $w('text password textarea');
		
		list.each(function(row) {
			if (!_.input.type(row, ins)) return;
			
			skip = false;
			for (var j = 0; j < end_sf; j++) {
				if (sf[j] == row.id && !skip) skip = true;
			}
			
			if (skip) return;
			
			if (_.empty($F(row.id)) && !first) {
				first = true;
				_._focus(row.id);
			}
		});
		return false;
	},
	form: {
		numbers: function(e) {
			var key;
			var keyr;
			
			key = Try.these(
				function() { return window.event.keyCode; },
				function() { return e.which; }
			) || true;
			if (key === true) {
				return key;
			}
			
			keyr = String.fromCharCode(key);
			all_key = [0, Event.KEY_BACKSPACE, Event.KEY_TAB, Event.KEY_RETURN, Event.KEY_ESC];
			
			if ((key == null) || $.inArray(key, all_key) || (keyr == '.' && !_.h($F(Event.element(e)), '.')) || (("0123456789").indexOf(keyr) > -1)) {
				return true;
			}
			
			return Try.these(
				function() { return Event.stop(e); },
				function() { return e.returnValue = false; }
			) || false;
		},
		submit: function(f, callback, a_args) {
			//_.error.hide();
			if (!this.isEmpty(f)) return false;
			
			_.form.checkbox(f);
			
			arg = [];
			for (var i = 0, end = _.len(f.elements); i < end; i++) {
				if (!_.el(f, i).name) continue;
				
				var arg_value = _.el(f, i).value;
				if (_.input.type(_.el(f, i), ['checkbox']) && !_.el(f, i).checked) {
					arg_value = '';
				}
				_.add(arg, _.el(f, i).name + '=' + _.encode(arg_value));
			}
			
			if (!_.empty(a_args)) _.add(arg, a_args);
			
			return _.call(f.action, callback, _.glue(arg, '&'));
		},
		complete: function(t) {
			var response = t.responseText;
			err = false;
			
			if (_.error.has(response)) {
				err = true;
				_.error.show(0, response);
			}
			
			for (var i = 0, end = _.len(f.elements); i < end; i++) {
				if (_.el(f, i).name && !_.input.type(_.el(f, i), ['submit'])) {
					if (!err) _.v(_.el(f, i), '');
					
					if (_.input.type(_.el(f, i), ['text']) && !i) _._focus(_.el(f, i));
				}
			}
			return false;
		},
		sEmpty: function(f) {
			var response = true;
			if (!_.form.isEmpty(f)) {
				response = false;
			} else {
				_.form.checkbox(f);
			}
			return response
		},
		isEmpty: function(f) {
			try {
				var f_skip = $F('skip').split(',');
			} catch(ce) {
				var f_skip = [];
			}
			
			err = 0;
			_in = $w('select hidden');
			
			for (var i = 0, end = _.len($(f)); i < end; i++) {
				el = _.el(f, i);
				if (!el.value && !$.inArray(el.name, f_skip) && !_.input.type(el, _in)) {
					if (!err) _._focus(el);
					
					err = 1;
				}
			}
			return !err;
		},
		first: function(el) {
			Form.focusFirstElement(el);
		},
		changed: function(el) {
			response = false;
			el.each(function(row) {
				if ($(row) && $.trim($F(row)) != '') {
					response = true;
					throw $break;
				}
			});
			return response;
		},
		selectindex: function(el, str) {
			try {
				$(el).selectedIndex = $A($(el).getElementsByTagName('option')).find( function(node){ return (node.text == _.entity_decode(str)); }).index;
			} catch (ce) { }
			
			return false;
		},
		selectedindex: function(el) {
			try {
				el = $(el);
				return el.options[el.selectedIndex].value;
			} catch (ce) { }
			
			return false;
		},
		firstOption: function(e, n) {
			if (!n) n = 0;
			
			try {
				var a = Element.findChildren(_.e(e), false, false, 'option');
				if (a[n]) {
					return [_.ga(a[n], 'value'), a[n].text];
				}
			} catch(xe) { }
			return [0, ''];
		},
		dynamic: {
			callbacks: [],
			
			create: function(el, f, nf) {
				if (!$(el)) {
					return;
				}
				
				var  fo = _.form.firstOption(el);
				new Insertion.Top($(el), _.form.add_option(el + '_cel', 'Crear elemento...'));
				_.form.selectindex(el, fo[1]);
				
				$.d.store('dsf_' + el, f);
				$.d.store('dnf_' + el, nf);
				Event.observe(el, 'change', _.form.dynamic.change);
			},
			change: function(e) {
				var el = _.e(e).id;
				
				if (_.form.selectedindex(el) == (el + '_cel')) {
					htm = Builder.node('span', [
						Builder.node('div', {id: 'ds_div_' + el}, [
						Builder.node('form', {method: 'post', id: 'ds_form_' + el, action: $.d.read('global_dynamic_select'), className: 'm_top_mid', onSubmit: "return _.form.submit(this, $.d.read('dsf_" + el + "'));"}, [
							Builder.node('input', {type: 'hidden', name: 'is', value: el}),
							Builder.node('input', {type: 'text', size: 25, id: 'ds_case_' + el, name: 'case'}),
							Builder.node('input', {type: 'submit', id: 'ds_submit_' + el, name: 'submit', value: 'Guardar'})
						])
						])
					]);
					new Insertion.After(el, _.code(htm));
					Event.observe('ds_case_' + el, 'blur', _.form.dynamic.auto);
					return _._focus('ds_case_' + el);
				}
				
				try { Element.remove('ds_div_' + el); } catch (ce) { }
				
				if ($.d.read('dnf_' + el)) {
					_._focus($.d.read('dnf_' + el));
				}
				return;
			},
			auto: function(e)
			{
				return _.click('ds_submit_' + _.replacement(_.e(e).id, 'ds_case_', ''));
			}
		},
		checkbox: function(f) {
			try {
			for (var i = 0, end = _.len(f.elements); i < end; i++) {
				if (!_.el(f, i).name) continue;
				
				if (_.input.type(_.el(f, i), ['checkbox']) && !_.el(f, i).checked) {
					_.el(f, i).checked = true;
					_.el(f, i).value = 0;
				}
			}
			} catch(ce) { }
			return;
		},
		add_option: function(a, b) {
			return '<option value="' + a + '">' + b + '</option>';
		}
	},
	input: {
		type: function(el, types, sign) {
			if (!sign) sign = '==';
			
			result = false;
			types.each(function(row) {
				eval("cmp = (el.type " + sign + " row);");
				if (cmp) {
					result = true;
					throw $break;
				}
			});
			return result;
		},
		replace: function(f, v) {
			f.each(function(row) {
				if ($F(row) == null) {
					_.v(row, v);
				}
			});
			return;
		},
		empty: function(a) {
			if (!a) a = 'stext';
			
			if (_.empty($F(a))) {
				return _._focus(a);
			}
			return true;
		},
		radio: function(a) {
			this.callback = function() {
				el = 'swyn_';
				
				z_class = ($('t' + a).value == 1) ? 'no' : 'yes';
				$('t' + a).value = (z_class == 'no') ? 0 : 1;
				Element.update(a, ((z_class == 'no') ? 'No' : 'Si'));
				
				Element.removeClassName(a, Element.classNames(a));
				Element.addClassName(a, el + z_class);
			}
			
			var e_input;
			if (e_input = _.tagname('input')) {
				for (var i = 0; e_input[i]; i++) {
					_.sa(e_input[i], 'autocomplete', 'off');
				}
			}
			Event.observe(a, 'click', this.callback);
			return;
		},
		select: {
			clear: function(e)
			{
				try {
				Element.findChildren($(e), false, false, 'option').each(function(row) {
					Element.remove(row);
				});
				} catch(ce) { }
				return false;
			}
		}
	},
	tagname: function(tag) {
		try {
			return document.getElementsByTagName(tag);
		} catch (ce) { }
		return false;
	},
	config: {
		store: function(k, v) {
			_.aconfig[k] = v;
			return;
		},
		read: function(k) {
			var response = false
			try {
				response = _.aconfig[k];
			} catch(ce) { }
			return response;
		}
	},
	error: {
		list: [],
		has: function(a) {
			return _.h(a.substr(0, 1), '#');
		},
		parse: function(a) {
			_.error.list.clear();
			
			if (_.error.has(a)) {
				a = a.substr(1);
			}
			
			_.split(a, '$').each(function(b) {
				if (!_.empty(b)) _.add(_.error.list, b);
			});
			return;
		},
		fshow: function(div, a) {
			Event.observe(window, 'load', function() { _.error.show(div, a); });
		},
		show: function(div, a) {
			if (!div) div = 'error_text';
			
			if (!_.empty(a)) _.error.parse(a);
			
			all = '<ul class="ul_none">';
			_.error.list.each(function(b) {
				all += '<li>' + b + '</li>';
			});
			all += '</ul>';
			
			Shadowbox.open({
				player: 'html',
				title: 'Error',
				content: all,
				height: 100,
				width: 250
			});
			return false;
		},
		hide_watch: function() {
			_.error.hide();
			return;
		},
		hide: function() {
			Element.hide('error');
			Element.update('error_text', '');
			return;
		}
	},
	skip: {
		list: [],
		add: function(a) {
			_.add(_.skip.list, a);
		},
		del: function(a) {
			delete _.skip.list[a];
		},
		read: function() {
			return _.skip.list;
		},
		reset: function() {
			_.skip.list.clear();
		}
	},
	tab: {
		ary: [],
		refresh: function() {
			a = _.split($.d.read('tab_refresh'), ' ');
			_.tab.x(a[0], a[1], a[2]);
			return false;
		},
		observe: function(el) {
			a = Element.findChildren($(el), false, true, 'li');
			a.each(function(row) {
				Event.observe(row.id, 'click', _.tab.click);
				_.add(_.tab.ary, _.replacement(row.id, 'row_', ''));
				
				if (_.len(a) == 1) _.tab.click(row.id);
			});
			return;
		},
		click: function(e) {
			el = _.e(e);
			tab_id = _.replacement(el.id, 'row_', '');
			
			if (el.id == $.d.read('tab_last')) {
				_.tab.remove(el.id + '_s', tab_id);
				$.d.store('tab_last', '');
			} else {
				if (!_.empty($.d.read('tab_last'))) {
					_.tab.remove($.d.read('tab_last') + '_s', _.replacement($.d.read('tab_last'), 'row_', ''));
				}
				if (el.id != $.d.read('tab_last')) {
					$.d.store('tab_last', el.id);
				}
				ff = _.replacement(_.code('tab_format'), /_dd/g, '_' + tab_id);
				new Insertion.After(el.id, '<li id="' + el.id + '_s">' + ff + '</li>');
				
				$w($.d.read('xtab_tags')).each(function(tab) {
					Event.observe('tab_' + tab + '_' + tab_id, 'click', _.tab.z);
				});
				
				_.tab.z('tab_general_' + tab_id);
			}
			return;
		},
		remove: function(el, i) {
			$w($.d.read('xtab_tags')).each(function(tab) {
				Event.stopObserving('tab_' + tab + '_' + i, 'click', _.tab.z);
			});
			Element.remove(el);
			return;
		},
		x: function(_a, _b, _c) {
			$.d.store('tab_refresh', _a + ' ' + _b + ' ' + _c);
			return _.call(_b, _.tab._x);
		},
		_x: function(t) {
			response = t.responseText;
			if (_.error.has(response)) {
				return _.error.show(0, response);
			}
			Element.update('tab_frame_' + tab_id, response);
			$('tab_scroll_' + tab_id).scrollTo();
			return;
		},
		z: function(e) {
			el = _.split(_.e(e).id, '_');
			return _.tab.x(el[2], _.replacement(_.replacement($.d.read('u_tab'), '*', el[2]), '?', el[1]), el[1]);
		}
	}
}