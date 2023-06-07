
var subtotalSummaryJsConf = {
	langs:{
		'SubtotalSummaryTitle' : 'Quick summary'
	},
	useOldSplittedTrForLine : 0
};

/**
 * SOMMAIRE DES TITRE (du module sous total)
 */
$( document ).ready(function() {

	let $tablelines = $('#tablelines tr[data-issubtotal="title"]');
	let summaryLines = [];

	if($tablelines.length > 0){
		$tablelines.each(function( index ) {
			let $subTotalLabel = $( this ).find('.subtotal_label:first');
			if($subTotalLabel.length > 0){
				summaryLines.push({
					id: $( this ).attr('data-id'),
					label: $subTotalLabel.text(),
					level: $( this ).attr('data-level')
				})
			}
		});
	}

	if(summaryLines.length>0){
		let summaryMenu = document.createElement('div');
		summaryMenu.id = 'subtotal-summary-let-menu-contaner';

		let summaryMenuTitle = document.createElement('h6');
		summaryMenuTitle.id = 'subtotal-summary-title';
		summaryMenuTitle.innerHTML = subtotalSummaryJsConf.langs.SubtotalSummaryTitle;
		summaryMenu.appendChild(summaryMenuTitle);


		summaryLines.forEach(function(item){
			let link = document.createElement('a');


			let paddingChars = ''
			for (let i = 1; i < parseInt(item.level); i++) {
				paddingChars+= '-';
			}

			link.innerText = paddingChars + ' ' + item.label;

			// link.style.paddingLeft = ((parseInt(item.level)-1)*5) + 'px';


			link.classList.add('subtotal-summary-link');
			link.href = '#row-'+ item.id;
			link.setAttribute('data-id', item.id);
			link.setAttribute('data-level', item.level);
			link.setAttribute('title', item.label);

			link.addEventListener('click', function(e) {
				e.preventDefault();

				let targetItem = document.getElementById( 'row-' + this.getAttribute('data-id') );

				$(targetItem).offset().top

				window.scroll({
					behavior: 'smooth',
					left: 0,
					top: $(targetItem).offset().top - 150
				});
			});

			summaryMenu.appendChild(link);
		});

		let leftMenu = document.getElementById('id-left');
		if(leftMenu != null){
			leftMenu.parentNode.appendChild(summaryMenu);
		}

	}

	/**
	 * Update menu active on scroll and resize
	 */

	let isInViewport = function isInViewport(element) {
		const rect = element.getBoundingClientRect();
		return ( rect.top >= 0 && rect.bottom <= (window.innerHeight || document.documentElement.clientHeight));
	}

	let checkMenuActiveInViewPort = function (){
		$('.subtotal-summary-link').each(function(i) {
			let targetId = $(this).attr('data-id');
			let targetElem = document.getElementById('row-' + targetId);
			if(targetElem != null){
				if(isInViewport(targetElem)){
					$(this).addClass('--target-in-viewport');
				}else{
					let atLeastOneChildInViewPort = false;

					let children = getSubtotalTitleChilds($('#row-' + targetId));
					if(children.length > 0){
						children.forEach(function(item){
							let targetChildElem= document.getElementById(item);
							if(targetChildElem != null){
								if(isInViewport(targetChildElem)){
									atLeastOneChildInViewPort = true;
									return true;
								}
							}
						});
					}

					if(atLeastOneChildInViewPort) {
						$(this).addClass('--child-in-viewport');
					}else{
						$(this).removeClass('--target-in-viewport --child-in-viewport');
					}
				}
			}
		});
	};


	/**
	 *
	 * @param {JQuery} $item
	 * @returns {*[]}
	 */
	let getSubtotalTitleChilds = function($item)
	{
		let TcurrentChilds = []; // = JSON.parse(item.attr('data-childrens'));
		let level = $item.attr('data-level');

		let indexOfFirstSubtotal = -1;
		let indexOfFirstTitle = -1;

		$item.nextAll('[id^="row-"]').each(function(index){

			let dataLevel = $(this).attr('data-level');
			let dataIsSubtotal = $(this).attr('data-issubtotal');

			if(dataIsSubtotal != 'undefined' && dataLevel != 'undefined' )
			{

				if(dataLevel <=  level && indexOfFirstSubtotal < 0 && dataIsSubtotal == 'subtotal' )
				{
					indexOfFirstSubtotal = index;
					if(indexOfFirstTitle < 0)
					{
						TcurrentChilds.push($(this).attr('id'));
					}
				}

				if(dataLevel <=  level && indexOfFirstSubtotal < 0 && indexOfFirstTitle < 0 && dataIsSubtotal == 'title' )
				{
					indexOfFirstTitle = index;
				}
			}

			if(indexOfFirstTitle < 0 && indexOfFirstSubtotal < 0)
			{
				TcurrentChilds.push($(this).attr('id'));

				// Add extraffield support for dolibarr > 7
				let thisId = $(this).attr('data-id');
				let thisElement = $(this).attr('data-element');

				if(thisId != undefined && thisElement != undefined && subtotalSummaryJsConf.useOldSplittedTrForLine )
				{
					$('[data-targetid="' + thisId + '"][data-element="extrafield"][data-targetelement="'+ thisElement +'"]').each(function(index){
						TcurrentChilds.push($(this).attr('id'));
					});
				}

			}

		});

		return TcurrentChilds;
	}

	// on page load
	checkMenuActiveInViewPort();

	// on page scroll or resize
	$(window).on('resize scroll', function() {
		checkMenuActiveInViewPort();
	});


});
