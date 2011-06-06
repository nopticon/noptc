<!-- INCLUDE js/grid.js -->
<!-- INCLUDE js/jquery.inputlabel.js -->

var EE = {
	OK: '~[200]'
}

function home_browse_type()
{
	$('.browse_type').click(function() {
		$.ajax({
			type: "POST",
			cache: false,
			url: _.config.read('u_home'),
			data: "ghost=1&t=" + _.replacement(this.id, 'type_', ''),
			success: function(t){
				$('#u_reference').html(t);
				arrange();
				home_browse_type();
			}
		});
	});
}

$(home_browse_type);
$(function() {
	$("#address").defaultValue("Email");
	$("#pkey").defaultValue("Clave");
});

$(function() {
	$('#reference .eachforum a').click(function() {
		$.ajax({
			type: "POST",
			cache: false,
			url: this.href,
			data: 'ghost=1',
			success: function(t){
				$('#f_list').html(t);
				arrange();
			}
		});
		
		return false;
	});
});

var _ = {
	aconfig: [],
	
	properties: function(el, a) {
		var response = '';
		for (v in a) {
			response += v + ' > ' + a[v] + '<br />';
		}
		Element.update(el, response);
		return;
	},
	timeout: function(cmd, s) {
		return setTimeout(cmd, (s * 1000));
	},
	amp: function(v, z) {
		return ((v != '') ? '&' + (z ? v : '') : '');
	},
	len: function(a) {
		try { return a.length; } catch(ce) { return 0; }
	},
	el: function(f, i) {
		return f.elements[i];
	},
	e: function(e) {
		try { el = Event.element(e); } catch(ce) { el = $(e); }
		return el;
	},
	ga: function(el, k) {
		return el.getAttribute(k);
	},
	sa: function(el, k, v) {
		return $(el).setAttribute(k, v);
	},
	isset: function(a) {
		return ((typeof a !== 'undefined') ? true : false);
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
		s.each(function(z, i){
			a += ((i) ? g : '') + z;
		});
		return a;
	},
	replacement: function(s, a, b) {
		return s.replace(a, b);
	},
	inArray: function(needle, haystack, strict) {
		var r = false;
		var s = '==' + ((strict) ? '=' : '');
		haystack.each(function(a) {
			eval('cmp = (needle ' + s + ' a) ? true : false;');
			if (cmp) { r = true; throw $break; }
		});
		return r;
	},
	fill: function(v, s) {
		return (v) ? v : s;
	},
	forceArray: function(a) {
		if (typeof a == 'string' || typeof a == 'number') {
			a = { };
		}
		return a;
	},
	add: function(a, v) {
		a.push(v);
		return;
	},
	encode: function(s) {
		if (typeof s != 'string') {
			eval("s = '" + s + "'");
		}
		return encodeURIComponent(_.trim(s));
	},
	call: function(u, callback, arg, async) {
		if (!u) return false;
		async = (async === false) ? false : true;
		
		var opt = {
			method: 'post',
			asynchronous: async,
			postBody: 'ajax=1' + _.amp(_.fill(arg, ''), true),
			onSuccess: callback,
			onFailure: function(t) {
				return _.error.show(0, t.statusText);
			}
		}
		new Ajax.Request(u, opt);
		return false;
	},
	fp: function(a, b) {
		response = [];
		a.each(function(z, i) {
			_.add(response, z + '=' + (($(b[i])) ? _.encode($F(b[i])) : ''));
		});
		
		return _.glue(response, '&');
	},
	v: function(el, v, a) {
		el = $(el);
		if (el) {
			if (a && !_.empty($F(el))) {
				v = $F(el) + a + v;
			}
			
			el.value = v;
		}
		return false;
	},
	go: function(u) {
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
		Element.update(e, '');
		
		try { Element.show('search-box'); } catch(ce) { }
		
		return;
	},
	observe: function(d, e) {
		Event.observe(d, 'click', function() { _.clear(e) });
		return;
	},
	display: function(el) {
		return Element.getStyle(el, 'display');
	},
	code: function(el) {
		return $(el).innerHTML;
	},
	stripTags: function(string) {
		return string.replace(/(<([^>]+)>)/ig, '');
	},
	trim: function(string) {
		return string.replace(/^\s+|\s+$/g, '');
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
		try {
			$(a).focus();
		} catch(ce) { }
		return false;
	},
	_efocus: function(a) {
		_.v(a, '');
		_._focus(a);
	},
	_toggle: function(a, b) {
		Element.hide(a);
		Element.show(b);
	},
	login: function() {
		this.callback = function() {
			_.form.first('login');
		}
		Event.observe(window, 'load', this.callback);
		return;
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
			
			if ((key == null) || _.inArray(key, all_key) || (keyr == '.' && !_.h($F(Event.element(e)), '.')) || (("0123456789").indexOf(keyr) > -1)) {
				return true;
			}
			
			return Try.these(
				function() { return Event.stop(e); },
				function() { return e.returnValue = false; }
			) || false;
		},
		submit: function(f, callback, a_args) {
			_.error.hide();
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
			
			//Form.disable(f);
			//_.form.disable();
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
		enable: function() {
			s = $('submit');
			if (!s) return false;
			
			if (s.disabled != '') {
				s.disabled = '';
			}
			return false;
		},
		disable: function() {
			s = $('submit');
			if (!s) return false;
			
			s.blur();
			s.disabled = 'true';
			
			_.timeout(_.form.enable, 1);
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
				if (!el.value && !_.inArray(el.name, f_skip) && !_.input.type(el, _in)) {
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
				if ($(row) && _.trim($F(row)) != '') {
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
				
				_.config.store('dsf_' + el, f);
				_.config.store('dnf_' + el, nf);
				Event.observe(el, 'change', _.form.dynamic.change);
			},
			change: function(e) {
				var el = _.e(e).id;
				
				if (_.form.selectedindex(el) == (el + '_cel')) {
					htm = Builder.node('span', [
						Builder.node('div', {id: 'ds_div_' + el}, [
						Builder.node('form', {method: 'post', id: 'ds_form_' + el, action: _.config.read('global_dynamic_select'), className: 'dynamic_select', onSubmit: "return _.form.submit(this, _.config.read('dsf_" + el + "'));"}, [
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
				
				if (_.config.read('dnf_' + el)) {
					_._focus(_.config.read('dnf_' + el));
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
		show: function(div, a) {
			if (!div) div = 'error_text';
			
			if (!_.empty(a)) _.error.parse(a);
			
			all = '<ul>';
			_.error.list.each(function(b) {
				all += '<li>' + b + '</li>';
			});
			all += '</ul>';
			
			Element.update(div, all);
			Element.setOpacity('error', 0.8);
			Element.show('error');
			Event.observe('error_hide', 'click', _.error.hide);
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
			a = _.split(_.config.read('tab_refresh'), ' ');
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
			
			if (el.id == _.config.read('tab_last')) {
				_.tab.remove(el.id + '_s', tab_id);
				_.config.store('tab_last', '');
			} else {
				if (!_.empty(_.config.read('tab_last'))) {
					_.tab.remove(_.config.read('tab_last') + '_s', _.replacement(_.config.read('tab_last'), 'row_', ''));
				}
				if (el.id != _.config.read('tab_last')) {
					_.config.store('tab_last', el.id);
				}
				ff = _.replacement(_.code('tab_format'), /_dd/g, '_' + tab_id);
				new Insertion.After(el.id, '<li id="' + el.id + '_s">' + ff + '</li>');
				
				$w(_.config.read('xtab_tags')).each(function(tab) {
					Event.observe('tab_' + tab + '_' + tab_id, 'click', _.tab.z);
				});
				
				_.tab.z('tab_general_' + tab_id);
			}
			return;
		},
		remove: function(el, i) {
			$w(_.config.read('xtab_tags')).each(function(tab) {
				Event.stopObserving('tab_' + tab + '_' + i, 'click', _.tab.z);
			});
			Element.remove(el);
			return;
		},
		x: function(_a, _b, _c) {
			_.config.store('tab_refresh', _a + ' ' + _b + ' ' + _c);
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
			return _.tab.x(el[2], _.replacement(_.replacement(_.config.read('u_tab'), '*', el[2]), '?', el[1]), el[1]);
		}
	},
	ztab: {
		ary: [],
		tags: [],
		last: '',
		push: function(a) {
			_.add(_.tab.ary, a);
			return;
		},
		s_tags: function(a) {
			_.tab.tags = a;
			return;
		},
		refresh: function() {
			a = _.tab.last.split(';');
			_.tab.s(a[0], a[1], a[2]);
			return false;
		},
		observe: function(el) {
			try {
				Element.findChildren($(el), false, true, 'h3').each(function(row) {
					Event.observe(row.id, 'click', _.tab.s2);
					
					a_el = _.split(row.id, '_');
					_.tab.push(a_el[2]);
					
					if (end == 1) _.tab.s2(row.id);
				});
			} catch(ce) { }
			return;
		},
		s: function(id, url, el) {
			this.callback = function(t) {
				response = t.responseText;
				if (_.error.has(response)) {
					return _.error.show(0, response);
				}
				
				Element.update('tab_frame_' + id, response);
			}
			
			if (_.tab.last) {
				a = _.tab.last.split(';');
				if (a[2]) Element.removeClassName('tab_' + a[2] + '_' + a[0], 'active');
			}
			
			Element.addClassName('tab_' + el + '_' + id, 'active');
			_.tab.last = id + ';' + url + ';' + el;
			return _.call(url, this.callback);
		},
		s2: function(e) {
			el = _.e(e);
			e_el = _.split(el.id, '_');
			
			_.tab.ary.each(function(row) {
				eval("$('each_' + row)." + ((row == e_el[2]) ? 'toggle' : 'hide') + "();");
				_show = (_.display('each_' + row) != 'none');
				if (_show) _.tab.s(row, _.replacement(_.replacement(_.config.read('u_tab'), '*', row), '?', 'general'), 'general');
				
				_.tab.tags.each(function(_row) {
					eval("Event." + ((_show) ? 'observe' : 'stopObserving') + "('tab_' + _row + '_' + row, 'click', _.tab.s3);");
				});
			});
			return;
		},
		s3: function(e) {
			el = _.e(e);
			e_el2 = _.split(el.id, '_');
			_.tab.s(e_el[2], _.replacement(_.replacement(_.config.read('u_tab'), '*', e_el2[2]), '?', e_el2[1]), e_el2[1]);
			return;
		}
	},
	startup: function() {
		if (!_.len(_.error.list)) {
			_.error.hide_watch();
		}
		_.config.store('chat_bgtime', 60)
		
		try {
			$('redbar').hide();
		} catch(ce) { }
		
		//chat.background();
		return;
	}
}