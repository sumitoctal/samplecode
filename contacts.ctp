<div class="top" style="border-bottom:1px solid #ccc;">
	<h1 class="pagehead"><?php echo __('Contacts'); ?></h1>
	
	<div class="top_right">
		<a class="search_icon" href="javascript:void(0);">
			<img src="<?php echo SITE_URL?>img/search_icon.png" alt="FitMeNow"/>
		</a>
		<a href="javascript:void(0);"  data-ajax="false" class="plus_icon">
			<span class="genericon genericon-plus calender_header_icon"></span>
		</a>		
	</div>		
</div>
	
<div class="agenda" style="position:relative;">
	<div class="contact_search_box"><?php echo $this->Form->input('searchitem',array("type"=>"text","label"=>false,"div"=>false,"data-type"=>"search" ,"placeholder"=>"Search","default"=>$srh_txt));?></div>	
	<div>
		<ul class="contact_list_main" data-role="listview" data-filter="true" data-input="#searchitem" data-autodividers="false" data-inset="false">	
		<?php
		if(empty($contacts))
		{
			echo __('Contacts not found');
		}
		$first_name="";
		$prev_first_name="";
		$pack_price = "";	
		
		foreach($contacts as $key=>$val)
		{
			if(!empty($val['Client']['first']) || !empty($val['Client']['last']))
			{
				$first_name=strtoupper(substr($val['Client']['first'],0,1));
				if($val['Client']['first']=="")
				{
					$first_name=strtoupper(substr($val['Client']['last'],0,1));
				}
				if($first_name != $prev_first_name)
				{					
					echo '<li data-role="list-divider" role="heading" id="'.$first_name.'">'.$first_name.'</li>';					
					$prev_first_name = $first_name;
				}	
		?>				
				<li class="contact_list" booking-id="<?php echo $val['Booking']['id'];?>" booking-startdate="<?php echo $val['Booking']['startdate'];?>" client-user-id="<?php echo $val['Client']['user_id']; ?>">				
					<div class="contact_image">
						<?php $image_url = 'img/instructor_default.png';
						if(!empty($val['Client']['avatar']) && file_exists('uploads/profile/thumb/' .$val['Client']['avatar']))
						$image_url = 'uploads/profile/thumb/' .$val['Client']['avatar'];
						?>
						<img alt="" src="<?php echo SITE_URL .$image_url;?>">
					</div>					
					<div class="contact_info">
					<h2 class="contact_name"><?php echo $val['Client']['first'].' '.$val['Client']['last'];?>
					<?php
						if(in_array($val['Client']['user_id'],$unpaid_session))
						echo '<span class="contact_exclamation">!</span>';
						?>
					</h2>
					</div>					
				</li>	
		<?php 	
			}
		}			
		?>					
		</ul>	
	</div>	
</div>
<script type="text/javascript">
$(".search_icon").click(function(e) {	
	mobile_changepage("instructors/search");
});

$(".plus_icon").click(function(e) {	
	mobile_changepage("instructors/contact_add/contacts");
});
 
$(".alphabets-rht > li > a").click(function(e) {
	var id = $(this).text();
	var id = id.toLowerCase(); 	
	if($("#"+id).length > 0){
		$('html, body').animate({scrollTop: $("#"+id).offset().top}, 'slow', 'swing');
	}
	return false;
});
$('.contact_list').on('click', function() {
	var client_user_id = $(this).attr('client-user-id');	
	mobile_changepage("trainings/personal_training_calendar_new/"+client_user_id);	
});

$(function() 
{	
	var srh_txt='<?php echo $srh_txt;?>';
	if(srh_txt !="")
	{
		$("#searchitem").trigger("keyup");
	}
});
</script>