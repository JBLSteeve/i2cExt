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
        if (init(_cmd.logicalId) == 'nbimpulsionminute') {
			tr += '<textarea class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="calcul" style="height : 33px;" placeholder="{{Calcul}}"></textarea> (utiliser #brut# dans la formule)';
		}
        if (init(_cmd.logicalId) == 'reel') {
			tr += '<textarea class="cmdAttr form-control input-sm Formule" data-l1key="configuration" data-l2key="calcul" style="height : 33px;" placeholder="{{Calcul}}"></textarea>';
			tr += '<a class="btn btn-default cursor listEquipementInfo" data-input="calcul" style="margin-top : 5px;"><i class="fa fa-list-alt "></i> {{Rechercher équipement}}</a>';
			tr += '<select class="cmdAttr form-control tooltips input-sm choixFormule" style="margin-top : 5px;" title="{{Formule standard}}" data-l1key="configuration" data-l2key="type">';
			tr += '<option value=""></option>';
			tr += '<option value="LM35Z">Sonde LM35Z</option>';
			tr += '<option value="T4012">Sonde T4012</option>';
			tr += '<option value="Voltage">Voltage</option>';
			tr += '<option value="SHT-X3L">SHT-X3:Light-LS100</option>';
			tr += '<option value="SHT-X3T">SHT-X3:Temp-LS100</option>';
			tr += '<option value="SHT-X3H">SHT-X3:RH-SH100</option>';
			tr += '<option value="SHT-X3HC">SHT-X3:RH-SH100 compensé</option>';
			tr += '<option value="TC100">TC 100</option>';
			tr += '<option value="CT10A">X400 CT10A</option>';
			tr += '<option value="CT20A">X400 CT20A</option>';
			tr += '<option value="CT50A">X400 CT50A</option>';
			tr += '<option value="Ph">X200 pH Probe</option>';
			tr += '<option value="Autre">Autre</option>';
			tr += '</select>';
		}
        tr += '</td>';
        tr += '<td>';
        if (init(_cmd.logicalId) == 'reel' || init(_cmd.logicalId) == 'nbimpulsionminute') {
			tr += '<input class="cmdAttr form-control input-sm" data-l1key="unite" style="width : 90px;" placeholder="{{Unite}}">';
		} else {
			tr += '<input type=hidden class="cmdAttr form-control input-sm" data-l1key="unite" value="">';
		}
        if (init(_cmd.logicalId) == 'reel') {
			tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="minValue" placeholder="{{Min}}" title="{{Min}}" style="width : 40%;display : inline-block;"><br>';
			tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="maxValue" placeholder="{{Max}}" title="{{Max}}" style="width : 40%;display : inline-block;"><br>';
		} else {
			tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="minValue" placeholder="{{Min}}" title="{{Min}}" style="width : 40%;display : none;"> ';
			tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="maxValue" placeholder="{{Max}}" title="{{Max}}" style="width : 40%;display : none;">';
		}
        tr += '</td>';
        tr += '<td>';
        tr += '<span><input type="checkbox" class="cmdAttr" data-l1key="isHistorized"/> {{Historiser}}<br/></span>';
        tr += '<span><input type="checkbox" class="cmdAttr" data-l1key="isVisible" checked/> {{Afficher}}<br/></span>';
		if (init(_cmd.subType) == 'binary') {
			tr += '<span class="expertModeVisible"><input type="checkbox" class="cmdAttr" data-l1key="display" data-l2key="invertBinary" /> {{Inverser}}<br/></span>';
		}
        if (init(_cmd.logicalId) == 'reel') {
			tr += '<span class="expertModeVisible"><input type="checkbox" class="cmdAttr" data-l1key="configuration" data-l2key="minValueReplace" value="1"/> {{Correction Min	 Auto}}<br>';
			tr += '<input type="checkbox" class="cmdAttr" data-l1key="configuration" data-l2key="maxValueReplace" value="1"/> {{Correction Max Auto}}<br></span>';
		}        tr += '</td>';
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

// Ajout pour les adresses des cartes
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

     if (!isset(_eqLogic)) {
        var _eqLogic = {configuration: {}};
    }

    if (!isset(_eqLogic.configuration)) {
        _eqLogic.configuration = {};
    }


            updateAddressEqLogicList(getCardAddress());


    $('body').setValues(_eqLogic, '.eqLogicAttr'); 

}

function updateAddressEqLogicList(_listEqLogicByType) {
    var optionList =['<option value="none" selected>{{Non affectées}}</option>'];
switch($('[data-l1key=configuration][data-l2key=board]'.val())){
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

$('.eqLogicAction[data-action=hide]').on('click', function () {
    var eqLogic_id = $(this).attr('data-eqLogic_id');
    $('.sub-nav-list').each(function () {
		if ( $(this).attr('data-eqLogic_id') == eqLogic_id ) {
			$(this).toggle();
		}
    });
    return false;
});

$("#table_cmd_i2cExt_relai").sortable({axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});
$("#table_cmd_i2cExt_bouton").sortable({axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});
$("#table_cmd").sortable({axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});