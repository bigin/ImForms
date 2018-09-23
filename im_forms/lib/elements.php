<?php namespace ImForms;

class ImFormsForm
{
	public $itemId = null;
	public $class = null;
	public $id = null;
	public $style = null;
	public $resources = null;
	public $name = '';
	public $enctype = null;
	public $charset = null;
	public $novalidate = null;
	public $action = '';
	public $content = null;
	public $method = 'post';
	public $formtype = null;

	public $elements = array();
	public $lastId = 0;

	public function set($key, $value) { $this->{$key} = $value; }

	public function add($element, $id = null) {
		if($id) { $this->elements[$id] = $element; }
		else { $this->elements[] = $element; }
	}

	public function renderResources() {
		return preg_replace('%\[\[( *)siteurl( *)\]\]%', IM_SITE_URL, stripslashes($this->resources));
	}

	public function render()
	{
		$this->generateContent();

		$trigger = '<input type="hidden" name="imforms" value="'.$this->name.'">';

		$markup = '<form';
		$markup .= (($this->class) ? ' class="'.$this->class.'"' : '');
		$markup .= (($this->id) ? ' id="'.$this->id.'"' : '');
		$markup .= (($this->style) ? ' style="'.$this->style.'"' : '');
		$markup .= (($this->name) ? ' name="'.$this->name.'"' : '');
		$markup .= (($this->charset) ? ' accept-charset="'.$this->charset.'"' : '');
		$markup .= (($this->enctype) ? ' enctype="'.$this->enctype.'"' : '');
		$markup .= (($this->novalidate) ? ' novalidate' : '');
		$markup .= ' action="'.$this->action.'"';
		$markup .= (($this->method) ? ' method="'.$this->method.'"' : '');
		$markup .= ">\r\n";
		$markup .= (($this->content) ? $this->content.$trigger."\r\n</form>\r\n" : $trigger."\r\n</form>\r\n");
		return $markup;
	}

	protected function generateContent()
	{
		if(!empty($this->elements)) {
			foreach($this->elements as $element) {
				if(is_object($element)) $this->content .= $element->render();
				else $this->content .= $element;
			}
		}
	}
}


class ImFormsInput
{
	public $type = 'text';
	public $class = null;
	public $id = null;
	public $parentid = null;
	public $style = null;
	public $name = null;
	public $value = null;
	public $placeholder = null;
	public $required = null;
	public $size = null;
	public $maxlength = null;
	public $multiple = null;

	public function set($key, $value) { $this->{$key} = $value; }

	public function render()
	{
		$markup = '<input type="'.$this->type.'"';
		$markup .= (($this->class) ? ' class="'.$this->class.'"' : '');
		$markup .= (($this->id) ? ' id="'.$this->id.'"' : '');
		$markup .= (($this->style) ? ' style="'.$this->style.'"' : '');
		$markup .= (($this->name) ? ' name="'.$this->name.'"' : '');
		$markup .= (($this->value) ? ' value="'.$this->value.'"' : '');
		$markup .= (($this->maxlength) ? ' maxlength="'.$this->maxlength.'"' : '');
		$markup .= (($this->size) ? ' size="'.$this->size.'"' : '');
		$markup .= (($this->placeholder) ? ' placeholder="'.$this->placeholder.'"' : '');
		$markup .= (($this->multiple) ? ' multiple="multiple"' : '');
		$markup .= (($this->required) ? ' required="required"' : '');
		$markup .= ">\r\n";

		return $markup;
	}
}


class ImFormsFieldset
{
	public $class = null;
	public $id = null;
	public $parentid = null;
	public $style = null;
	public $legend = null;
	public $content = null;

	public $elements = array();

	public function set($key, $value) { $this->{$key} = $value; }

	public function add($element, $id = null) {
		if($id) { $this->elements[$id] = $element; }
		else { $this->elements[] = $element; }
	}

	public function render()
	{
		$this->generateContent();

		$markup = '<fieldset';
		$markup .= (($this->class) ? ' class="'.$this->class.'"' : '');
		$markup .= (($this->id) ? ' id="'.$this->id.'"' : '');
		$markup .= (($this->style) ? ' style="'.$this->style.'"' : '');
		$markup .= ">\r\n";
		$markup .= (($this->legend) ? "<legend>$this->legend</legend>\r\n" : '');
		$markup .= (($this->content) ? "$this->content</fieldset>\r\n" : "</fieldset>\r\n");
		return $markup;
	}

	protected function generateContent()
	{
		if(!empty($this->elements)) {
			foreach($this->elements as $element) {
				if(is_object($element)) $this->content .= $element->render();
				else $this->content .= $element;
			}
		}
	}
}


class ImFormsLabel
{
	public $class = null;
	public $id = null;
	public $parentid = null;
	public $for = null;
	public $style = null;
	public $content = null;
	public $textappend = null;

	public $elements = array();

	public function set($key, $value) { $this->{$key} = $value; }

	public function add($element, $id = null) {
		if($id) { $this->elements[$id] = $element; }
		else { $this->elements[] = $element; }
	}

	public function render()
	{
		$this->generateContent();

		$markup = '<label';
		$markup .= (($this->class) ? ' class="'.$this->class.'"' : '');
		$markup .= (($this->id) ? ' id="'.$this->id.'"' : '');
		$markup .= (($this->style) ? ' style="'.$this->style.'"' : '');
		$markup .= (($this->for) ? ' for="'.$this->for.'"' : '');
		$markup .= '>';
		$markup .= (($this->content) ? "$this->content</label>\r\n" : "</label>\r\n");

		return $markup;
	}

	protected function generateContent()
	{
		$buffer = null;
		if(!empty($this->elements)) {
			foreach($this->elements as $element) {
				if(is_object($element)) {
					$buffer .= (!$this->textappend) ? "\r\n".$element->render() : rtrim($element->render(), "\r\n");
				}
				else $buffer .= $element;
			}
			$this->content = ($this->textappend) ? "$buffer $this->content" : "$this->content $buffer";
		}
	}
}


class ImFormsWrapper
{
	public $tag = 'div';
	public $class = 'form-group';
	public $id = null;
	public $parentid = null;
	public $style = null;
	public $content = null;

	public $elements = array();

	public function set($key, $value) { $this->{$key} = $value; }

	public function add($element, $id = null) {
		if($id) { $this->elements[$id] = $element; }
		else { $this->elements[] = $element; }
	}

	public function render()
	{
		$this->generateContent();

		$markup = '<'.$this->tag;
		$markup .= (($this->class) ? ' class="'.$this->class.'"' : '');
		$markup .= (($this->id) ? ' id="'.$this->id.'"' : '');
		$markup .= (($this->style) ? ' style="'.$this->style.'"' : '');
		$markup .= ">\r\n";
		$markup .= (($this->content) ? "$this->content</$this->tag>\r\n" : "</$this->tag>\r\n");

		return $markup;
	}

	protected function generateContent()
	{
		if(!empty($this->elements)) {
			foreach($this->elements as $element) {
				if(is_object($element)) $this->content .= $element->render();
				else $this->content .= $element;
			}
		}
	}
}


class ImFormsTextarea
{
	public $class = null;
	public $id = null;
	public $parentid = null;
	public $style = null;
	public $rows = '10';
	public $cols = '60';
	public $name = null;
	public $placeholder = null;
	public $required = null;
	public $content = null;

	public function set($key, $value) { $this->{$key} = $value; }

	public function render()
	{
		$markup = '<textarea';
		$markup .= (($this->class) ? ' class="'.$this->class.'"' : '');
		$markup .= (($this->id) ? ' id="'.$this->id.'"' : '');
		$markup .= (($this->style) ? ' style="'.$this->style.'"' : '');
		$markup .= (($this->name) ? ' name="'.$this->name.'"' : '');
		$markup .= (($this->rows) ? ' rows="'.$this->rows.'"' : '');
		$markup .= (($this->cols) ? ' cols="'.$this->cols.'"' : '');
		$markup .= (($this->placeholder) ? ' placeholder="'.$this->placeholder.'"' : '');
		$markup .= (($this->required) ? ' required="required"' : '');
		$markup .= '>';
		$markup .= (($this->content) ? "$this->content</textarea>\r\n" : "</textarea>\r\n");

		return $markup;
	}
}


class ImFormsReCaptcha
{
	public $site_key = null;
	public $secret_key = null;
	public $class = 'g-recaptcha';
	public $datasize = null;
	public $parentid = null;

	public function set($key, $value) { $this->{$key} = $value; }

	public function render() {
		return '<div '.(($this->class) ? ' class="'.$this->class.'" ' : '').'data-sitekey="'.$this->site_key.'" '.
			(($this->datasize) ? 'data-size='.$this->datasize : '').'></div>';
	}
}


class ImFormsButton
{
	public $type = 'submit';
	public $class = null;
	public $id = null;
	public $parentid = null;
	public $style = null;
	public $name = null;
	public $content = null;

	public function set($key, $value) { $this->{$key} = $value; }

	public function render()
	{
		$markup = '<button type="'.$this->type.'"';
		$markup .= (($this->class) ? ' class="'.$this->class.'"' : '');
		$markup .= (($this->id) ? ' id="'.$this->id.'"' : '');
		$markup .= (($this->style) ? ' style="'.$this->style.'"' : '');
		$markup .= (($this->name) ? ' name="'.$this->name.'"' : '');
		$markup .= '>';
		$markup .= (($this->content) ? "$this->content</button>\r\n" : "</button>\r\n");

		return $markup;
	}

}


class ImFormsDelayBlock
{
	public $content = null;
	public $parentid = null;

	public function set($key, $value) { $this->{$key} = $value; }

	public function render()
	{
		ob_start(); ?>
		<div id="delay">
			<div id="clamp">
				<a id="stop-delay" href="#">&nbsp;</a>
				<span id="loader"></span><p id="delay-info" class="blink"><?php echo $this->content; ?></p>
			</div>
		</div>
		<?php return ob_get_clean();
	}
}


class ImFormsAjaxBlock
{
	public $stopdelayid = 'stop-delay';
	public $formid = null;
	public $parentid = null;

	public function render()
	{
		ob_start(); ?>
		<script>
		$(document).ready(function() {
			$("#<?php echo $this->stopdelayid; ?>").click(function(e) {
				e.preventDefault();
				$("#delay").fadeOut();
			});
			$(document).on({
				ajaxStart: function() { $("#delay").show(); },
				ajaxStop: function() { $("#delay").fadeOut();}
			});
			var frm = $("#<?php echo $this->formid ?>");
			frm.submit(function(e) {
				e.preventDefault();
				//Declaring new Form Data Instance
				var formData = new FormData(this);
				formData.append('imforms_ajax', 1);
				$.ajax({
					dataType: "json",
					type: frm.attr("method"),
					url: frm.attr("action"),
					data: formData, //frm.serialize(),
					cache: false,
					contentType: false,
					processData: false,
					success: function (data) {
						//console.log(data);
						if(data && data.msgs) {
							if($('#msgs-wrapper').length == 0) {
								$('<div id="msgs-wrapper">'+data.msgs+"</div>").insertBefore('#<?php echo $this->formid ?>');
							} else {
								$("#msgs-wrapper").replaceWith('<div id="msgs-wrapper">'+data.msgs+"</div>");
							}
							if(data.status == 1) { $("#<?php echo $this->formid ?>")[0].reset();}
							var el = $("#msgs-wrapper");
							var elOffset = el.offset().top;
							var elHeight = el.height();
							var windowHeight = $(window).height();
							var offset;

							if (elHeight < windowHeight) { offset = elOffset - ((windowHeight / 2) - (elHeight / 2)); }
							else { offset = elOffset; }
							$("html, body").animate({
								scrollTop: offset
							});
							if(window.grecaptcha) { grecaptcha.reset(); }
						}
					},
					error: function (data) {
						console.log(data);
					},
				});
			});
		});
		</script>
		<?php return ob_get_clean();
	}
}