

if (typeof getSubtotalTitleChilds !== "function") {
	/**
	 * @param {JQuery} $item
	 * @param {bool} removeLastSubtotal remove last subtotal if it is the subtotal of the title
	 * @returns {*[]}
	 */
	function getSubtotalTitleChilds($item, removeLastSubtotal = false) {
		let TcurrentChilds = []; // = JSON.parse(item.attr('data-childrens'));
		let level = $item.attr('data-level');

		let indexOfFirstSubtotal = -1;
		let indexOfFirstTitle = -1;

		$item.nextAll('[id^="row-"]').each(function (index) {

			let dataLevel = $(this).attr('data-level');
			let dataIsSubtotal = $(this).attr('data-issubtotal');

			if (dataIsSubtotal != 'undefined' && dataLevel != 'undefined') {

				if (dataLevel <= level && indexOfFirstSubtotal < 0 && dataIsSubtotal == 'subtotal') {
					indexOfFirstSubtotal = index;
					if (indexOfFirstTitle < 0) {
						TcurrentChilds.push($(this).attr('id'));
					}
				}

				if (dataLevel <= level && indexOfFirstSubtotal < 0 && indexOfFirstTitle < 0 && dataIsSubtotal == 'title') {
					indexOfFirstTitle = index;
				}
			}

			if (indexOfFirstTitle < 0 && indexOfFirstSubtotal < 0) {
				TcurrentChilds.push($(this).attr('id'));

				// Add extraffield support for dolibarr > 7
				let thisId = $(this).attr('data-id');
				let thisElement = $(this).attr('data-element');

				if (thisId != undefined && thisElement != undefined && subtotalSummaryJsConf.useOldSplittedTrForLine) {
					$('[data-targetid="'+thisId+'"][data-element="extrafield"][data-targetelement="'+thisElement+'"]').each(function (index) {
						TcurrentChilds.push($(this).attr('id'));
					});
				}
			}
		});

		// remove last subtotal if it is the subtotal of the title
		if(removeLastSubtotal && TcurrentChilds.length > 0){
			let lastChildId= TcurrentChilds.slice(-1);
			let $lastChild = $('#'+lastChildId);
			if($lastChild.length > 0 && $lastChild.attr('data-issubtotal') != undefined && $lastChild.attr('data-issubtotal') == 'subtotal'){
				if(level == $lastChild.attr('data-level') ){
					TcurrentChilds.pop();
				}
			}
		}

		return TcurrentChilds;
	}
}