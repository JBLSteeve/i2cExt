function addCmdToTable(_cmd) {
   if (!isset(_cmd)) {
        var _cmd = {configuration: {}};
    }
    if (!isset(_cmd.configuration)) {
        _cmd.configuration = {};
    }

    if (init(_cmd.type) == 'info') {
        var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '" >';
        if (init(_cmd.logicalId) == 'brut') {
			tr += '<input type="hiden" name="brutid" value="' + init(_cmd.id) + '">';
		}
        tr += '<td>';
        tr += '<span class="cmdAttr" data-l1key="id"></span>';
        tr += '</td>';
        tr += '<td>';
        tr += '<input class="cmdAttr form-control input-sm" data-l1key="name" style="width : 140px;" placeholder="{{Nom}}"></td>';
		tr += '<td class="expertModeVisible">';
        tr += '<input class="cmdAttr form-control type input-sm" data-l1key="type" value="action" disabled style="margin-bottom : 5px;" />';
        tr += '<span class="subType" subType="' + init(_cmd.subType) + '"></span>';
        tr += '</td>';
        tr += '<td>';
        tr += '</td>';
        tr += '<td>';
		tr += '<input type=hidden class="cmdAttr form-control input-sm" data-l1key="unite" value="">';
		tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="minValue" placeholder="{{Min}}" title="{{Min}}" style="width : 40%;display : none;"> ';
		tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="maxValue" placeholder="{{Max}}" title="{{Max}}" style="width : 40%;display : none;">';
        tr += '</td>';
        tr += '<td>';
        tr += '<span><input type="checkbox" class="cmdAttr" data-l1key="isHistorized"/> {{Historiser}}<br/></span>';
        tr += '<span><input type="checkbox" class="cmdAttr" data-l1key="isVisible" checked/> {{Afficher}}<br/></span>';
		if (init(_cmd.subType) == 'binary') {
			tr += '<span class="expertModeVisible"><input type="checkbox" class="cmdAttr" data-l1key="display" data-l2key="invertBinary" /> {{Inverser}}<br/></span>';
		}
        tr += '</td>';
        tr += '<td>';
        if (is_numeric(_cmd.id)) {
            tr += '<a class="btn btn-default btn-xs cmdAction expertModeVisible" data-action="configure"><i class="fa fa-cogs"></i></a> ';
        }
        tr += '</td>';
		table_cmd = '#table_cmd';
		if ( $(table_cmd+'_'+_cmd.eqType ).length ) {
			table_cmd+= '_'+_cmd.eqType;
		}
        $(table_cmd+' tbody').append(tr);
        $(table_cmd+' tbody tr:last').setValues(_cmd, '.cmdAttr');
    }
    if (init(_cmd.type) == 'action') {
        var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
        tr += '<td>';
        tr += '<span class="cmdAttr" data-l1key="id"></span>';
        tr += '</td>';
        tr += '<td>';
        tr += '<input class="cmdAttr form-control input-sm" data-l1key="name" style="width : 140px;" placeholder="{{Nom}}">';
        tr += '</td>';
        tr += '<td>';
        tr += '<input class="cmdAttr form-control type input-sm" data-l1key="type" value="action" disabled style="margin-bottom : 5px;" />';
        tr += '<span class="subType" subType="' + init(_cmd.subType) + '"></span>';
        tr += '<input class="cmdAttr" data-l1key="configuration" data-l2key="virtualAction" value="1" style="display:none;" >';
        tr += '</td>';
        tr += '<td>';
        tr += '</td>';
        tr += '<td></td>';
        tr += '<td>';
        tr += '<span><input type="checkbox" class="cmdAttr" data-l1key="isVisible" checked/> {{Afficher}}<br/></span>';
        tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="minValue" placeholder="{{Min}}" title="{{Min}}" style="width : 40%;display : none;">';
        tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="maxValue" placeholder="{{Max}}" title="{{Max}}" style="width : 40%;display : none;">';
        tr += '</td>';
        tr += '<td>';
        if (is_numeric(_cmd.id)) {
            tr += '<a class="btn btn-default btn-xs cmdAction expertModeVisible" data-action="configure"><i class="fa fa-cogs"></i></a> ';
            tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fa fa-rss"></i> {{Tester}}</a>';
        }
        tr += '</td>';
        tr += '</tr>';

		table_cmd = '#table_cmd';
		if ( $(table_cmd+'_'+_cmd.eqType ).length ) {
			table_cmd+= '_'+_cmd.eqType;
		}
        $(table_cmd+' tbody').append(tr);
        $(table_cmd+' tbody tr:last').setValues(_cmd, '.cmdAttr');
        var tr = $(table_cmd+' tbody tr:last');
        jeedom.eqLogic.builSelectCmd({
            id: $(".li_eqLogic.active").attr('data-eqLogic_id'),
            filter: {type: 'info'},
            error: function (error) {
                $('#div_alert').showAlert({message: error.message, level: 'danger'});
            },
            success: function (result) {
                tr.find('.cmdAttr[data-l1key=value]').append(result);
                tr.setValues(_cmd, '.cmdAttr');
            }
        });
    }
}

/////////////////////////////////////////////////////////////////////////////
// Ajout pour les adresses des cartes
/////////////////////////////////////////////////////////////////////////////

function getCardAddress() {
    var eqLogic = new Object();
    $.ajax({
        type: 'POST',
        async: false,
        url: 'plugins/i2cExt/core/ajax/i2cExt.ajax.php',
        data: {
            action: 'getCardAddress'
        },
        dataType: 'json',
        global: false,
        error: function (request, status, error) {
            handleAjaxError(request, status, error);
        },
        success: function (data) {
            if (data.state != 'ok') {
                $('#div_alert').showAlert({message: data.result, level: 'danger'});
                return;
            }
            if (data.result.length != 0) {
                eqLogic = data.result;
            }
        }
    });
    return eqLogic;
}

function printEqLogic(_eqLogic) {
	console.log('fonction printEqLogic');
	
     if (!isset(_eqLogic)) {
        var _eqLogic = {configuration: {}};
    }

    if (!isset(_eqLogic.configuration)) {
        _eqLogic.configuration = {};
    }

	var cardAddress = getCardAddress();
	/*switch(_eqLogic.configuration.board) {
   	 case 'IN8R8':
      	   cardAddress['IN8R8_Address'].push(_eqLogic.configuration.address);
     	   break;
   	 case 'IN4DIM4':
    		cardAddress['IN4DIM4_Address'].push(_eqLogic.configuration.address);
     	   break;
   	 default:
       	 break;
	} */
	updateAddressEqLogicList(cardAddress);

    $('body').setValues(_eqLogic, '.eqLogicAttr'); 

}

function updateAddressEqLogicList(_listEqLogicByType) {
    var optionList =['<option value="none" selected>{{Non affectée}}</option>'];
switch($('[data-l1key=configuration][data-l2key=board]').val()){
	case 'IN8R8':
    	for (var i = 0; i < _listEqLogicByType.IN8R8_Address.length; i++) {
        	optionList.push('<option value="', _listEqLogicByType.IN8R8_Address[i], '"');
         	optionList.push('>', _listEqLogicByType.IN8R8_Address[i], '</option>');
    	}
	break;
	case 'IN4DIM4':
  		for (var i = 0; i < _listEqLogicByType.IN4DIM4_Address.length; i++) {
        	optionList.push('<option value="', _listEqLogicByType.IN4DIM4_Address[i], '"');
         	optionList.push('>', _listEqLogicByType.IN4DIM4_Address[i], '</option>');
    	}
	break;
}

    $('[data-l1key=configuration][data-l2key=address]').html(optionList.join(''));
}

$('[data-l1key=configuration][data-l2key=board]').on('change', function() {
console.log("fonction mise à jour liste board");
//console.log($('[data-l1key=configuration][data-l2key=address]').val());

//$('[data-l1key=configuration][data-l2key=address]').empty();
//updateAddressEqLogicList(getCardAddress());
/*if($('[data-l1key=configuration][data-l2key=board]').val() == "") {
	$('[data-l1key=configuration][data-l2key=address]').empty();
	}

//$('[data-l1key=configuration][data-l2key=address]').empty();
console.log($('[data-l1key=configuration][data-l2key=address]').val());

	var cardAddress = getCardAddress();
	switch($('[data-l1key=configuration][data-l2key=board]').val()) {
   	 case 'IN8R8':
      	   cardAddress['IN8R8_Address'].push($('[data-l1key=configuration][data-l2key=address]').val());
      	   console.log("add adress IN8R8");
      	   console.log($('[data-l1key=configuration][data-l2key=address]').val());
     	   break;
   	 case 'IN4DIM4':
    		cardAddress['IN4DIM4_Address'].push($('[data-l1key=configuration][data-l2key=address]').val());
     	   break;
   	 default:
       	 break;
	} 
*/	
	updateAddressEqLogicList(getCardAddress());

});


$('.eqLogicAction[data-action=hide]').on('click', function () {
    var eqLogic_id = $(this).attr('data-eqLogic_id');
    $('.sub-nav-list').each(function () {
		if ( $(this).attr('data-eqLogic_id') == eqLogic_id ) {
			$(this).toggle();
		}
    });
    return false;
});

$("#table_cmd_i2cExt_output").sortable({axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});
$("#table_cmd_i2cExt_input").sortable({axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});
$("#table_cmd").sortable({axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});