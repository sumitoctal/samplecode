<?php
echo $this->Html->script('mobiscroll_js/mobiscroll.core');
echo $this->Html->script('mobiscroll_js/mobiscroll.frame');
echo $this->Html->script('mobiscroll_js/mobiscroll.scroller');

echo $this->Html->script('mobiscroll_js/mobiscroll.util.datetime');
echo $this->Html->script('mobiscroll_js/mobiscroll.datetimebase');
echo $this->Html->script('mobiscroll_js/mobiscroll.datetime');	
echo $this->Html->script('mobiscroll_js/mobiscroll.select');

echo $this->Html->css('mobiscroll_css/mobiscroll.animation');
echo $this->Html->css('mobiscroll_css/mobiscroll.icons');

echo $this->Html->css('mobiscroll_css/mobiscroll.scroller');
echo $this->Html->css('mobiscroll_css/mobiscroll.frame');

echo $this->Html->script('slick');
echo $this->Html->css('slick');
?>	
<div class="top" style="background:#FFF">	
	<h1 class="pagehead" id="main_pagehead"><?php echo date('M Y',$current_date); ?></h1>
	
	<div class="top_right">	
		<a href="javascript:void(0);" class="add_icon" style="float:right;">
			<string name="vertical_ellipsis" class="vertical_ellipse">&#8942;</string>
		</a>
		<a class="logolink" href="<?php echo SITE_URL; ?>instructors/search" style="float:right;">
			<img src="<?php echo SITE_URL?>img/search_icon.png" alt="FitMeNow"/>
		</a>		
	</div>	
</div>		

<div class="agenda" style="background:#FFF"> 	
	<div class="alert_list">
		<?php echo $this->element('calendar_alert');?>		
	</div>
	<div class="head" style="background-color:#FFF !important;width:100%;float:left;">		
		<?php // Show Week Calender ?>
		<div id="week_cal" style="display:block;">
			<div class="date-slider today-slider">			
				<?php											
				//$maxDays=date('t',$month_str);			
				$st = strtotime(date('m/1/Y',strtotime('-1 months')));
				$dotw = date('w', $st);
				$st = ($dotw == 0) ? $st : strtotime('last Sunday', $st);				
				
				$end = strtotime(date('m/t/Y',strtotime('+2 months')));
				$slide_no=0;
				$default_slide_no=0;
				while($st<=$end)
				{	
					$class="";
					if(date('m/d/Y',$st)==$user_time)
					$class = "current-day";
					
					if(date('m/d/Y',$st)==$user_time && $default_date==null)
					$class .= " active-day";
					
					if(date('m/d/Y',$st)==date('m/d/Y',$default_date))					
					{
					$default_slide_no=$slide_no;	
					$class = " active-day";	
					}
					else if($st==$current_date)
					{
					$default_slide_no=$slide_no;
					}
									
					echo '<div rel="'.$st.'" class="day_'.$st.'" style="width:100px;"><span class="day-name">'.date('D',$st).'</span><span class="daydt_agenda_all daydt '.$class.'" rel="'.$st.'" data-slide="'.$slide_no.'">'.date('j',$st).'</span><br/></div>';	

					$st = $st +(24*60*60);
					$slide_no++;
				}
				$default_slide=0;
				if($default_slide_no > 0)
				{
					$default_slide = (floor($default_slide_no/7)*7);
				}
				?>
			</div>
		</div>
		
		<?php // Show Month Calender ?>
		<div id="month_cal" style="visibility:hidden">
			<div class="date-slider month-slider">
				<?php
				$slide_no=0;
				for($m=-1;$m<=2;$m++)
				{
					$month_str=strtotime('+'.$m.' months');			
				?>
					<div>			
						<table border="0" width="100%">
						<tr class="header_row">
						<td style="text-align:left"><span class="day-name"><?php echo __('Sun');?></span></td>	
						<td style="text-align:left"><span class="day-name"><?php echo __('Mon');?></span></td>	
						<td style="text-align:left"><span class="day-name"><?php echo __('Tue');?></span></td>
						<td style="text-align:left"><span class="day-name"><?php echo __('Wed');?></span></td>	
						<td style="text-align:left"><span class="day-name"><?php echo __('Thu');?></span></td>
						<td style="text-align:left"><span class="day-name"><?php echo __('Fri');?></span></td>	
						<td style="text-align:left"><span class="day-name"><?php echo __('Sat');?></span></td>
						</tr>
						<tr>
						<?php
						$maxDays=date('t',$month_str);
						echo date('M Y',$month_str);
						$month_start_day=date('w',mktime(0,0,0,date('m',$month_str),1,date('y',$month_str)));
						$line=0;	
						if($month_start_day>=1)
						{	
							echo '<td colspan="'.$month_start_day.'"><span class="day-name"></span></td>';
							$line=$month_start_day;
						}			
						
						for($i=1;$i<=$maxDays;$i++)
						{	
							$class="";
							if($line%7==0)
								echo '</tr><tr>';
							$cdate=mktime(0,0,0,date('m',$month_str),$i,date('y',$month_str));				
							
							if(date('m/d/Y',$cdate)==$user_time)
							$class="current-day active-day";
								
							echo '<td><span class="day-name daydt '.$class.'" rel="'.$cdate.'" data-slide="'.$slide_no.'">'.$i.'</span></td>';
							$line++;
							$slide_no++;	
						}	
						?>	
						</tr>	
						</table>			
					</div>
				<?php
				}
				?>	
			</div>
		</div>
	</div>
	<div class="list_data">
	<div class="agenda_content">
	<div class="next-training instructor-dashboard calendar_data" style="position:relative;">
	<ul class="show cal_start_end_time">
	<?php
	$j=0;	
	
	$dt_arr=date_parse(date('m/d/Y',$current_date));		
	$interval  = 60 *  60;				
	$startdate = mktime(0,0,0,$dt_arr['month'],$dt_arr['day'],$dt_arr['year']);			
	$enddate = mktime(23,59,0,$dt_arr['month'],$dt_arr['day'],$dt_arr['year']);	
	$startdate1=$startdate;
	$enddate1=$enddate;
	while ($startdate < $enddate )
	{
		$dt = date("g:ia", $startdate);
		echo '<li class="cal_starttime" id="time_'.$dt.'">'.$dt.'</li>';			
		$startdate += $interval;			
	} 		
	?>
	</ul>
	<div style="position:relative">
	<ul class="show cal_start_end_time_right"  style="position:absolute;">
	<?php
	$j=0;	
	$dt_arr=date_parse(date('m/d/Y',$current_date));		
	$interval  = 30 *  60;				
	$startdate = mktime(0,0,0,$dt_arr['month'],$dt_arr['day'],$dt_arr['year']);			
	$enddate = mktime(23,59,0,$dt_arr['month'],$dt_arr['day'],$dt_arr['year']);	
	$startdate1=$startdate;
	$enddate1=$enddate;
	while ($startdate < $enddate )
	{
		$dt = date("g:ia", $startdate);
		echo '<li class="cal_starttime" id="time_'.$dt.'"></li>';			
		$startdate += $interval;			
	} 		
	?>
	</ul>
	
	<ul class="tr-session list_data_content">
	</ul>
	</div>
	
	</div>
	</div>
	</div>
	</div>
	<div class="clear"></div>
	<div class="agenda_new_all_bottom_div">
		<div class="agenda_new_all_link today"><?php echo __('Today');?></div>
		<div class="agenda_new_all_link month"><?php echo __('Month');?></div>
		<div class="agenda_new_all_link now"><?php echo __('Now');?></div>
	</div>
	<div class="clear"></div>
	<div id="cover" style="visibility:hidden" class="coverdiv">	
		<div class="product_overlay_link reservation"><span><img src="<?php echo SITE_URL; ?>img/reservation.png"><br/><?php echo __('Reservation');?></span></div>
		
		<div class="product_overlay_link personal_time"><span><img src="<?php echo SITE_URL; ?>img/personal_time.png"><br/><?php echo __('Personal Time');?></span></div>
			
		<div class="product_overlay_link connection"><span><img src="<?php echo SITE_URL; ?>img/communication.png"><br/><?php echo __('Connection');?></span></div>
		
		<div class="product_overlay_link quicksale"><span><img src="<?php echo SITE_URL; ?>img/dollor.png"><br/><?php echo __('Quick Sale');?></span></div>	
	</div> 
	<?php 
	echo $this->element('reservation_add_time');

	echo $this->element('reservation_add_personal_time');

	$first_scroll=0;
	if($default_date != null)
	{
		$first_scroll = ($default_date - $current_date)/(15*60) * 40;
	}
	?>
<script type="text/javascript">
function change_calender(day,display)
{
	var img = '<img src="<?php echo SITE_URL;?>img/ajax-loader_mobile.gif" style="position:fixed;top:50%;left:50%"/>';
	
	$(".list_data_content").html(img);

	//return true;	
	$.ajax({
		type: "POST",
		url: "<?php echo SITE_URL;?>instructors/agenda_new_ajax",
		data: { day: day,display:display}
	}).done(function( msg ) {			
		$(".list_data_content").html(msg);		
		$("#week_cal").show();
		$("#month_cal").hide();	
		$("#month_cal").css('visibility','visible');			

		var d=new Date(day*1000).toUTCString().split(' ');		
		var cleanDate = d[2] + ' ' + d[3] ;		
		$("h1#main_pagehead").html(cleanDate);			
					
	});		
}

function show_event(type)
{
	if (type==null)  
        type = 'reservation';
    
    var selDate=$("#demo_datetime").val();
    var duration = $("#InviteDuration").val();
    if(type=='personal')
    {
		selDate=$("#demo_datetime1").val();
		duration = $("#InviteDuration1").val();	
    }
    //console.log(selDate); 	
    var d=new Date(selDate).toString().split(' ');	
    var time=d[4];
    var t=time.split(':');			
    var scr=(t[0] * 60) - 230;
    if(type=='personal')
    {
        scr=(t[0] * 60) - 250;
    }	
    $(".list_data").scrollTop(scr); 

    var mon =new Date(selDate).getMonth();
    var year =new Date(selDate).getFullYear();
    var dt =new Date(selDate).getDate();
    var d2=new Date(Date.UTC(year,mon,dt,0,0,0));
    var new_dt=Math.floor(d2.getTime()/ 1000)
    /* console.log(d); 	
    console.log(d2);
    console.log(new_dt); */

    var slide = $(".today-slider .daydt[rel='"+new_dt+"']").attr('data-slide');
    $(".today-slider .daydt").removeClass('active-day');
    $(".today-slider .daydt[data-slide='"+slide+"']").addClass('active-day');
    $(".today-slider").slick('slickGoTo',slide);


    var day = $('.today-slider .daydt.active-day').attr('rel');						
    $.ajax({
        type: "POST",
        url: "<?php echo SITE_URL;?>instructors/agenda_new_ajax",
        data: { day: new_dt,selDate:selDate,duration:duration,}
    }).done(function( msg ) {			
        $(".list_data_content").html(msg);		
        $("#week_cal").show();
        $("#month_cal").hide();	
        $("#month_cal").css('visibility','visible');			

        var d=new Date(day*1000).toUTCString().split(' ');		
        var cleanDate = d[2] + ' ' + d[3] ;		
        $("h1#main_pagehead").html(cleanDate);			
    });
}	
	
$(function() 
{	
		
	var wh=$(window).height();
	var list_div_h=wh - 148;
	$(".list_data").height(list_div_h);	
		
	if(!$("ul.alert_row_list").has("li").length)
	{		
		$(".list_data").height(wh - 110);	
	}
	
	//var wd=$('.calendar_data').width();
	var wd=$('body.ui-mobile-viewport').width();
	//console.log(wd);	
	var list_div_w=wd - 50;
	$("ul.tr-session.list_data_content").width(list_div_w);
	
	$( window ).resize(function() {
	
		var wh=$(window).height();
		var list_div_h=wh - 125;
		$(".list_data").height(list_div_h);	
	
		var wd=$('.calendar_data').width();	
		var list_div_w=wd - 50;	
		$("ul.tr-session.list_data_content").width(list_div_w);
	});

	$(".list_data").scrollTop('360'); 

	var curr_time='<?php echo $default_date;?>';
	//console.log(curr_time);
	if(curr_time!="")
	{
		var d=new Date(curr_time*1000).toUTCString().split(' ');
		//console.log(d);
		var time=d[4];
		var t=time.split(':');
		//console.log(t);
		var h=(t[0]  * 60) - 60;
		//console.log(h);
		$(".list_data").scrollTop(h); 
	}
	else
	{
		//var d = new Date("July 21, 20163 01:15:00 PM");
		var d = new Date();
		var h = d.getHours()-1;
		//console.log(h);
		var top=h*60;		
		$(".list_data").scrollTop(top);
	}
	
	$(".reservation").on('click',function() {
		setdatetime();			
		$("#cover").css('visibility','hidden');		
		$("#reservation_add_time").css('visibility','initial');
		show_event('reservation');
		$("body").css('overflow','hidden');			
	});
	
	$(".personal_time").on('click',function() {	
		setdatetime1();	
		$("#cover").css('visibility','hidden');				
		$("#reservation_add_personal_time").css('visibility','initial');
		show_event('personal');
		$("body").css('overflow','hidden');			
	});
	
	$(".connection").on('click',function() {	
		window.location='<?php echo SITE_URL;?>instructors/contact_add/calendar';
	});
	
	$(".quicksale").on('click',function() {	
		window.location='<?php echo SITE_URL;?>inventories/quick_sale';
	});
	
	$(".add_icon").on('click',function() {
		var wh=$(window).height();
		var top_margin = (wh-470)/2;
		//console.log(top_margin);
		if(top_margin >=1)
		{
			$("#cover .product_overlay_link").css('top',top_margin)
		}
		$("#cover").css('visibility','initial');		
	});
	
	$("#cover").on('click',function() {
		$(this).css("visibility", 'hidden');		
	});
	
	$('.today-slider').slick({
		infinite: false,
		initialSlide:<?php echo $default_slide;?>,
		slidesToShow: 7,
		slidesToScroll: 7, 
		<?php
		if($mobile_req)
		echo 'arrows:false,';
		?>		
	}); 
	
	$('.today-slider').on('afterChange', function(event, slick, currentSlide, nextSlide){
		var slide_first_day = $( ".today-slider .daydt[data-slide='"+(currentSlide+3)+"']").attr('rel');		
		var d=new Date(slide_first_day*1000).toUTCString().split(' ');				
		var cleanDate = d[2] + ' ' + d[3] ;		
		$("h1#main_pagehead").html(cleanDate);
	});
	
	var current_slide= $('.today-slider').slick('slickCurrentSlide');
	
	$('.today-slider').slick('slickGoTo',current_slide);

	change_calender('<?php echo $current_date;?>');

	var content = $(document);
	
	$(document).on('click', '.booking_info', function() {
		var book_id = $( this ).attr('book-id');
		var startdate = $( this ).attr('book-startdate');
		mobile_changepage("trainings/session_profile/"+book_id+"/"+startdate);			
	});
	
	$(document).on('click', '.group_booking_info', function() {
		var book_id = $( this ).attr('book-id');
		var startdate = $( this ).attr('book-startdate');
		mobile_changepage("trainings/group_training_view/"+book_id+"/"+startdate);			
	});		
	
	$(document).on('click', '.class_info', function() {	
		var class_id = $( this ).attr('class-id');
		mobile_changepage("instructors/class_view/"+class_id+'/calendar');					
	});
	
	$(document).on('click', '.booking_info_class', function() {		
		var class_id = $( this ).attr('class-id');
		var book_startdate = $( this ).attr('book-startdate');
		mobile_changepage("instructors/booking_info_class/"+class_id+'/'+book_startdate+'/calendar');	
	});		
	
	$(".today-slider span.daydt").on('click',function() {				
		var time = $( this ).attr('rel');		
		change_calender(time);
		$('span.daydt').removeClass('active-day');	
		$(this).addClass('active-day');		
	});	
	
	$(".month-slider span.daydt").on('click',function() {		
		var time = $( this ).attr('rel');
		var slide = $( ".today-slider .daydt[rel='"+time+"']").attr('data-slide');
		change_calender(time);
		$('.month').removeClass('month_open');
		$('.month').html('Month');			
		//var slide = $( this ).attr('data-slide');
		//var default_slide = (Math.floor(slide/7)*7);
		$('#week_cal').show();	
		$('#month_cal').hide();	
		var slider = $('.today-slider');
		slider[0].slick.slickGoTo(slide);
		
		$('.today-slider .daydt').removeClass('active-day').removeClass('current-day1');
		$( ".today-slider .daydt[data-slide='"+slide+"']" ).addClass('active-day');				
	});	
	
	$(".today").on('click',function() {	

		var slide_no= $('.today-slider span.current-day').attr('data-slide');			
		$('.today-slider').slick('slickGoTo',slide_no);				
			
		$('#week_cal').show();		
		$('#month_cal').hide();
		$('#week_cal').addClass('week_cal_show');
		$('#month_cal').removeClass('month_cal_show');
		$('.daydt').removeClass('active-day');	
		$('.current-day').addClass('active-day');
		change_calender('<?php echo $current_date;?>');
		//$('.current-day').css('color', 'white');
	});
	
	var month_first_click=0;
	$(".month").on('click',function() {				
		if($(this).hasClass('month_open'))
		{
			$(this).removeClass('month_open');
			$(this).html('Month');
			$('#month_cal').hide();
			$('#week_cal').show();
			return true;
		}
		$('#week_cal').hide();		
		$('#month_cal').show();
		$(this).addClass('month_open');
		$(this).html('Week');
		if(month_first_click == 0)
		{		
			$('.month-slider').slick({
				infinite: false,
				initialSlide:1,
				slidesToShow: 1,
				slidesToScroll: 1,		
				<?php
				if($mobile_req)
				echo 'arrows:false,';
				?>
			});	
		}
		month_first_click=1;		
	});
	
	$(".now").on('click',function() {				
		//mobile_changepage("instructors/group_training_view_now");
		window.location="<?php echo SITE_URL;?>instructors/group_training_view_now";
	});

	$(".alert_coll").on('click',function(){	
		if($(this).hasClass('alert_heading'))
		{
			$(this).removeClass('alert_heading');
			$(this).addClass('alert_heading2');
		}else{
			$(this).removeClass('alert_heading2');
			$(this).addClass('alert_heading');
		}		
		$('.alert_row_list').toggle('slow');		
	});	
	
	$('.alertrow').on('click',function() 
	{
		var url = $(this).attr('data-url');	
		window.location = url;
	});
	
	$('.unpaid_session').on('click',function(){
		//var booking_id = $(this).attr('booking-id');	
		var client_id = $(this).attr('client-id');	
		window.location = "<?php echo SITE_URL;?>instructors/unpaid_resolution/"+client_id;
	});
});
</script>