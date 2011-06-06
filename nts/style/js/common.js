var EE = {
	OK: '~[200]'
};

$w('click change submit keypress blur').each(function(i) {
	Element.Methods['_' + i] = function(element, f) {
		element = $(element);
		element.observe(i, f);
		return element;
	};
	
	Element.Methods['un' + i] = function(element, f) {
		element = $(element);
		element.stopObserving(i, f);
		return element;
	}
});

Element.addMethods({
	html: function(element, value) {
		element = $(element);
		if (value === undefined) {
			return element ? element.innerHTML : null;
		}
		element.update(value);
		return element;
	},
	fixed: function(element) {
		element = $(element);
		element.addClassName('fixed');
		return element;
	},
	unfixed: function(element) {
		element = $(element);
		element.removeClassName('fixed');
		return element;
	},
	timeout: function(element, f, t) {
		element = $(element);
		
		_.timeout(function() {
			eval('element.' + f + '();');
		}, t);
		return element;
	}
});

function array_key(arr, k) {
	return arr[k];
};

function json_decode(a) {
	return a.evalJSON(true);
};

// http://kevin.vanzonneveld.net
// +   original by: Leslie Hoare
// +   bugfixed by: Onno Marsman
// %          note 1: See the commented out code below for a version which will work with our experimental (though probably unnecessary) srand() function)
// *     example 1: rand(1, 1);
// *     returns 1: 1
function rand(min, max) {
	var argc = arguments.length;
	if (argc === 0) {
		min = 0;
		max = 2147483647;
	} else if (argc === 1) {
		throw new Error('Warning: rand() expects exactly 2 parameters, 1 given');
	}
	return Math.floor(Math.random() * (max - min + 1)) + min;
}

var _ = {
	aconfig: [],
	
	timeout: function(cmd, s) {
		return setTimeout(cmd, (s * 1000));
	},
	len: function(a) {
		return Try.these(
			function() { return a.length; }
		) || 0;
	},
	e: function(e) {
		return Try.these(
			function() { return Event.element(e); },
			function() { return $(e); }
		) || e;
	},
	parent: function(e, rid) {
		e = _.e(e).parentNode;
		while (e.id == '') {
			e = e.parentNode;
		};
		if (rid) {
			return e.id;
		};
		return e;
	},
	ga: function(el, k) {
		return $(el).readAttribute(k);
	},
	sa: function(el, k, v) {
		return $(el).writeAttribute(k, v);
	},
	empty: function(e) {
		return (Object.isUndefined(e) || e == '' || !e);
	},
	split: function(s, a) {
		if (_.empty(a)) a = '|';
		
		return Try.these(function() { return s.split(a); }) || false;
	},
	glue: function(s, g) {
		a = '';
		s.each(function(z, i) {
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
			if (cmp) {
				r = true;
				return;
			}
		});
		return r;
	},
	extend: function(a, b) {
		var c = {};
		
		for (row in a) {
			if (!Object.isFunction(a[row])) c[row] = a[row];
		}
		for (row in b) {
			if (!Object.isFunction(b[row])) c[row] = b[row];
		}
		
		return c;
	},
	encode: function(s) {
		if (!Object.isString(s)) {
			s = s.toString();
		}
		return _.trim(s);
	},
	call: function(url, callback, arg, async) {
		if (!url) return false;
		async = (async === false) ? false : true;
		
		if (!Object.isObject(arg)) {
			arg = {};
		}
		arg.ghost = 1;
		
		var opt = {
			method: 'post',
			asynchronous: async,
			postBody: Object.toQueryString(arg),
			onSuccess: callback,
			onFailure: function(t) {
				return _.error.show(t.statusText);
			}
		};
		new Ajax.Request(url, opt);
		return false;
	},
	fp: function(a, b) {
		response = [];
		a.each(function(z, i) {
			var d = z;
			if (!Object.isUndefined(b[i])) {
				d = b[i];
			}
			response[z] = ($(d)) ? _.encode($F(d)) : '';
		});
		return response;
	},
	v: function(el, v, a) {
		return Try.these(function() {
			if (a && !_.empty($F(el))) {
				v = $F(el) + a + v;
 			}
			$(el).value = v;
		});
	},
	go: function(u) {
		if (_.h(u, 'Location')) {
			u = u.substr(10);
		}
		window.location = u;
		return;
	},
	reload: function() {
		window.location.reload();
		return false;
	},
	sim_click: function(el) {
		_.e(el).click();
		return false;
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
	trim: function(str) {
		return str.replace(/^\s\s*/, '').replace(/\s\s*$/, '');
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
		return Try.these(
			function() { $(a).activate(); }
		) || false;
	},
	_efocus: function(a) {
		_.v(a, '');
		_._focus(a);
		return false;
	},
	_toggle: function(a, b) {
		return Try.these(
			function() { $(a).hide(); $(b).show(); }
		) || false;
	},
	focus: function(el, sf) {
		if (!sf) sf = [];
		
		it = 'text password textarea';
		first = false;
		Form.getElements(el).each(function(i) {
			if (!_.input.type(i, it)) return;
			
			_skp = false;
			for (var j = 0, end = _.len(sf); j < end; j++) {
				if (sf[j] == i.id && !_skp) _skp = true;
			}
			
			if (_skp) return;
			
			if (_.empty($F(i.id)) && !first) {
				first = true;
				_._focus(i.id);
			}
		});
		return false;
	},
	has_error: function(a) {
		return _.h(a.substr(0, 1), '#');
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
			if (_.form.is_empty(f)) {
				return false;
			}
			
			arg = {};
			Form.getElements(f).each(function(i, j) {
				if (_.empty(i.name)) return;
				
				var arg_value = i.value;
				if (_.input.type(i, 'checkbox') && !i.checked) {
					arg_value = '';
				}
				
				if (_.h(i.name, '[')) {
					i.name = _.replacement(i.name, '[]', '[' + j + ']');
				}
				
				arg[i.name] = _.encode(arg_value);
			});
			
			if (Object.isObject(a_args)) {
				arg = _.extend(arg, a_args);
			};
			
			return _.call(f.action, callback, arg);
		},
		complete: function(t) {
			var response = t.responseText;
			err = false;
			
			if (_.error.has(response)) {
				err = true;
				_.error.show(response);
			}
			
			Form.getElements(f).each(function(i, j) {
				if (i.name && !_.input.type(i, 'submit')) {
					if (!err) _.v(i, '');
					
					if (_.input.type(i, 'text') && !j) _._focus(i);
				}
			});
			return false;
		},
		event: function(e) {
			Event.stop(e);
			return _.form.find(e);
		},
		find: function(e) {
			return Event.findElement(e, 'form');
		},
		_required: function(f) {
			return $(f).hasClassName('required');
		},
		required: function(f) {
			return $(f).select('.required');
		},
		error_or_go: function(t) {
			response = t.responseText;
			if (_.error.has(response)) {
				return _.error.show(response);
			}
			return _.go(response);
		},
		sEmpty: function(f) {
			var response = true;
			if (_.form.is_empty(f)) {
				response = false;
			} else {
				_.form.checkbox(f);
			}
			return response;
		},
		is_empty: function(f) {
			err = false;
			
			Form.getElements(f).each(function(i) {
				if (_.empty(i.value) && _.form._required(i) && !_.input.type(i, 'select hidden')) {
					if (!err) _._focus(i);
					
					err = true;
				}
			});
			return err;
		},
		first: function(el) {
			a = false;
			$(el).getInputs().each(function(i) {
				if (i.type == 'hidden' || i.disabled) {
					return;
				}
				
				if (!a) $(i).activate();
				
				a = true;
			});
			return;
		},
		changed: function(f) {
			response = false;
			$w(f).each(function(row) {
				if ($(row) && !_.empty(_.trim($F(row)))) {
					response = true;
					throw $break;
				}
			});
			return response;
		},
		firstOption: function(e, n) {
			if (!n) n = 0;
			
			return Try.these(function() {
				var a = Element.findChildren(_.e(e), false, false, 'option');
				if (a[n]) return [_.ga(a[n], 'value'), a[n].text];
			}) || [0, ''];
		}
	},
	input: {
		_type: function(i) {
			return (i.type != 'select-one') ? i.type : 'select';
		},
		type: function(el, k, sign) {
			if (!sign) sign = '==';
			
			el = $(el);
			result = false;
			$w(k).each(function(i) {
				el_type = _.input._type(el);
				
				eval('cmp = (el_type ' + sign + ' i);');
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
		option: function(a) {
			$$('.' + a).each(function(i) {
				$(i)._click(_.input.option_callback);
			});
		},
		option_callback: function(e) {
			e = $(_.e(e));
			a = array_key(_.split(Object.toHTML(e.classNames()), ' '), 0);
			
			$$('.' + a).each(function(i, j) {
				if (e.id === i.id)
				{
					_.v(_.replacement(a, 'sf_option_', ''), _.replacement(i.id, 'option_', ''));
					$(i).addClassName('sf_selectd');
				} else {
					$(i).removeClassName('sf_selectd');
				}
			});
			return;
		},
		select: {
			clear: function(e) {
				return $$('#' + e + ' option').invoke('remove');
			}
		}
	},
	config: {
		store: function(k, v) {
			if (Object.isObject(k)) {
				_.aconfig = _.extend(_.aconfig, k);
			} else {
				eval('_.aconfig = _.extend(_.aconfig, {' + k + ': v})');
			}
			return;
		},
		read: function(k) {
			return (!Object.isUndefined(_.aconfig[k])) ? _.aconfig[k] : false;
		}
	}
}

var contact = {
	startup: function() {
		Try.these(function() {
			$('form_message').hide();
			$('field_secure').writeAttribute('autocomplete', 'off');
			_.form.first('contact_form');
			
			$('secure_tag')._click(contact.refresh);
			$('secure_img')._click(contact.refresh);
			$('contact_form')._submit(contact.submit);
		});
	},
	submit: function(e) {
		f = _.form.event(e);
		if (_.form.is_empty(f))
		{
			return false;
		}
		
		return _.form.submit(f, contact.callback);
	},
	callback: function(t) {
		response = t.responseText;
		if (_.has_error(response))
		{
			response = response.substr(1);
			$('form_message').update(response).show();
			
			contact.refresh();
			return;
		}
		
		ret = json_decode(response);
		
		$('form_message').update(ret.lang).show();
		$('contact_form').hide().reset();
	},
	refresh: function(e) {
		h = $('secure_img').readAttribute('src');
		
		n = array_key(_.split(h, '?'), 0) + '?i=' + rand();
		$('secure_img').writeAttribute('src', n);
		_._efocus('field_secure');
		return h;
	}
}

$(contact.startup);