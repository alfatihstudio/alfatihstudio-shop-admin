(function($){'use strict';
 document.addEventListener('click',function(e){
  var pick=e.target.closest('.afs-writer-pick'),remove=e.target.closest('.afs-writer-remove');if(!pick&&!remove)return;
  e.preventDefault();var box=(pick||remove).closest('.afs-writer-photo'),field=box.querySelector('input'),preview=box.querySelector('.afs-writer-preview');
  if(remove){field.value='0';preview.replaceChildren();return;}
  var frame=wp.media({title:'Foto Penulis',button:{text:'Gunakan foto'},library:{type:'image'},multiple:false});
  frame.on('select',function(){var item=frame.state().get('selection').first().toJSON();field.value=item.id;var img=document.createElement('img');img.src=item.sizes&&item.sizes.thumbnail?item.sizes.thumbnail.url:item.url;img.alt='';preview.replaceChildren(img);});frame.open();
 });
 // Native taxonomy AJAX reads its form; synchronize TinyMCE before serialization.
 document.addEventListener('submit',function(e){if(e.target.id==='addtag'&&window.tinymce)tinymce.triggerSave();},true);
 $(document).ajaxSuccess(function(e,x,settings){if(typeof settings.data==='string'&&/(^|&)action=add-tag(&|$)/.test(settings.data)&&x.responseText.indexOf('<term_id>')!==-1){var box=document.querySelector('.afs-writer-photo');if(box){box.querySelector('input').value='0';box.querySelector('.afs-writer-preview').replaceChildren();}var bio=window.tinymce&&tinymce.get('afs-writer-bio');if(bio)bio.setContent('');}});
}(jQuery));
