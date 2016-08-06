$(function(){
   $("#show_catalog").click(function(){
      $("dl[ectype='dl_catalog'] dd").show();
      $("dl[ectype='dl_catalog']").show();
      //$(this).hide();
      $("#show_catalog").hide();
	  $("#hide_catalog").show();
   });
   $("#hide_catalog").click(function(){document.location.reload()});
   
})

 function stab(cur,name){
	for (var i=1;i<=4;i++){
		document.getElementById(name+'-'+i).className='';
	}
	document.getElementById(name+'-'+cur).className='stab-hover stab-hover-'+cur;
	
	for (i=1;i<=4;i++){
	   document.getElementById(name+'-content-'+i).style.display="none";
	}
	document.getElementById(name+'-content-'+cur).style.display="block";
 }

function searchtype(type){
   if (type=='goods'){
      document.getElementById('s_goods').className = 'current';
	  document.getElementById('s_store').className = '';
	  document.getElementById('s_groupbuy').className = '';
	  document.getElementById('search-act').value = 'index';
   }
   else if(type=='store'){
	  document.getElementById('s_goods').className = '';
	  document.getElementById('s_store').className = 'current';
	  document.getElementById('s_groupbuy').className = '';
	  document.getElementById('search-act').value = 'store';
   }
   else{
	  document.getElementById('s_goods').className = '';
	  document.getElementById('s_store').className = '';
	  document.getElementById('s_groupbuy').className = 'current';
	  document.getElementById('search-act').value = 'groupbuy';
   }
}
 