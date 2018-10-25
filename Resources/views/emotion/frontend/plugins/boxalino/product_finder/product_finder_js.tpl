var json =  {$Data.json_facets},
    lang  = '{$Data.locale}',
    url = '{url controller=cat sCategory=$Data.cpo_finder_link}',
    facets = new bxFacets(),
    max_score = {$Data.max_score},
    highlighted = {$Data.highlighted},
    selectedValues = {};
facets.init(json);

// Get the current facet
var currentFacet = null;
var questions = facets.getAdditionalFacets();
if(highlighted == false) {
  for(var i = 0; i < questions.length; i++) {
      var fieldName = questions[i];
      if(facets.getCurrentSelects(fieldName) === null) {
          currentFacet = fieldName;
          break;
      }
  }
}

// Get the field name of the expert facet
var expertFieldName =  facets.getDataOwnerFacet();

// Returns all the experts
var expertFacetValues = facets.getFacetValues(expertFieldName)[expertFieldName];

// default expert

var defaultExpert = null;
expertFacetValues.forEach(function(value) {
    //checks each sommelier if set to default
    if(facets.getFacetValueExtraInfo(expertFieldName, value, 'is-initial')) {
        defaultExpert = value;
    }
});

// check if data owner facet is selected
if (currentFacet == expertFieldName) {
  // name, persona & image for default expert

  var defaultExpertFirstName = facets.getFacetValueExtraInfo(expertFieldName, defaultExpert, 'first-name');
  var defaultExpertLastName  = facets.getFacetValueExtraInfo(expertFieldName, defaultExpert, 'last-name');
  var persona                = facets.getFacetValueExtraInfo(expertFieldName, defaultExpert, 'persona');
  var defaultExpertQuestionImage = facets.getFacetValueExtraInfo(expertFieldName, defaultExpert, 'question-img');

  // append default expert to template
  jQuery('.cpo-finder-left-content').append(expertHtml.replace('%%ExpertQuestionImage%%', defaultExpertQuestionImage)
  .replace('%%ExpertFirstName%%', defaultExpertFirstName)
  .replace('%%ExpertLastName%%', defaultExpertLastName)
  .replace('%%ExpertPersona%%', persona[lang]));

  // create all other experts

  expertFacetValues.forEach(function(value) {

    var expertFirstName = facets.getFacetValueExtraInfo(expertFieldName, value, 'first-name');
    var expertLastName  = facets.getFacetValueExtraInfo(expertFieldName, value, 'last-name');
    var selectionImg    = facets.getFacetValueExtraInfo(expertFieldName, value, 'selection-img');
    var characteristics    = facets.getFacetValueExtraInfo(expertFieldName, value, 'characteristics');

    // append experts to template

    jQuery('.cpo-finder-center-content-container').append(expertListHtml.replace('%%Characteristics0%%', characteristics[0]['alt-text'][lang])
    .replace('%%ExpertFirstName%%', expertFirstName)
    .replace('%%ExpertLastName%%', expertLastName)
    .replace('%%ExpertFirstName%%', expertFirstName)
    .replace('%%ExpertLastName%%', expertLastName)
    .replace('%%Characteristics0Value%%', characteristics[0]['property'][lang])
    .replace('%%Characteristics1%%', characteristics[1]['alt-text'][lang])
    .replace('%%Characteristics1Value%%', characteristics[1]['property'][lang])
    .replace('%%Characteristics2%%', characteristics[2]['alt-text'][lang])
    .replace('%%Characteristics2Value%%', characteristics[2]['property'][lang])
    .replace('%%ExpertSelectionImage%%', selectionImg));

    createFieldListener(value);

  });

  // get dataOwnerHeader

  var dataOwnerHeader = facets.getFacetExtraInfo(currentFacet, 'dataOwnerHeader');

  // append dataOwnerHeader

  jQuery('.cpo-finder-center-content-header').append(dataOwnerHeader);

  // create field listener for data owner

  function createFieldListener(value){

    var expertFirstName = facets.getFacetValueExtraInfo(expertFieldName, value, 'first-name');
    var expertLastName  = facets.getFacetValueExtraInfo(expertFieldName, value, 'last-name');

    jQuery('#' + expertFirstName + expertLastName + '_button').on('click', function() {
          facets.removeSelect(expertFieldName);
          facets.addSelect(expertFieldName, value);
          var params = facets.getFacetParameters();
          params.forEach(function(param, index) {
              if(index > 0) {
                  url += '&';
              } else {
                  url += '?'
              }
              if(param.indexOf('=') === -1){
                  url += param + '=100';
              } else {
                  url += param;
              }
          });
          window.location = url;
      });
    }
}else{
  // Get question of the current facet for the expert
  var finderQuestion = facets.getFacetExtraInfo(currentFacet, 'finderQuestion');
  if (facets.getCurrentSelects(expertFieldName)) {
      var selectedExpert = facets.getCurrentSelects(expertFieldName)[0];
  } else {
      var selectedExpert = defaultExpert;
  }
    // create expert
    var expertQuestionImg =   facets.getFacetValueExtraInfo(expertFieldName, selectedExpert, 'question-img');
    var expertFirstName =     facets.getFacetValueExtraInfo(expertFieldName, selectedExpert, 'first-name');
    var expertLastName =      facets.getFacetValueExtraInfo(expertFieldName, selectedExpert, 'last-name');
    var expertPersona =       facets.getFacetValueExtraInfo(expertFieldName, selectedExpert, 'persona');
    var expertExpertise =     facets.getFacetValueExtraInfo(expertFieldName, selectedExpert, 'expertise');
    var expertIntroSentence = facets.getFacetValueExtraInfo(expertFieldName, selectedExpert, 'intro-sentence');
    var expertQuestionSentence = facets.getFacetValueExtraInfo(expertFieldName, selectedExpert, 'question-sentence');
    var expertCharacteristics = facets.getFacetValueExtraInfo(expertFieldName, selectedExpert, 'characteristics');

  // get current facet values
  var currentFacetValues =   facets.getFacetValues(currentFacet)[currentFacet];

  // append expert to template
  jQuery('.cpo-finder-left-content').append(expertLeftHtml.replace('%%ExpertFirstName%%', expertFirstName)
      .replace('%%ExpertLastName%%', expertLastName)
      .replace('%%ExpertPersona%%', expertPersona[lang])
      .replace('%%ExpertExpertise%%', expertExpertise[lang])
      .replace('%%Characteristics0%%', expertCharacteristics[0]['alt-text'][lang])
      .replace('%%Characteristics0Value%%', expertCharacteristics[0]['property'][lang])
      .replace('%%Characteristics1%%', expertCharacteristics[1]['alt-text'][lang])
      .replace('%%Characteristics1Value%%', expertCharacteristics[1]['property'][lang])
      .replace('%%Characteristics2%%', expertCharacteristics[2]['alt-text'][lang])
      .replace('%%Characteristics2Value%%', expertCharacteristics[2]['property'][lang])
      .replace('%%ExpertQuestionImage%%', expertQuestionImg));

  // append intro sentance
  if (currentFacet != null) {
    facetLabel = facets.getFacetExtraInfo(currentFacet, 'finderQuestion');
    // only show intro sentance on the first question
    if (currentFacet == questions[1]) {
      jQuery('.cpo-finder-center-content-header').append(expertIntroSentence[lang]);
      jQuery('.cpo-finder-center-content-header').append(facetLabel);
    }
    else {
      // otherwise show question header
      jQuery('.cpo-finder-center-content-header').append(expertQuestionSentence[lang]);
      jQuery('.cpo-finder-center-content-header').append(facetLabel);
    }
  }

  // create fields

  createFields();

  // create button

  createButton();

  // createFields function

  function createFields(){

    container = jQuery('.cpo-finder-center-current-question-options');

    var fieldContainer = jQuery('<div class="' + currentFacet + '_container cpo-finder-answers-container"></div>');
    var facetValues = facets.getFacetValues(currentFacet)[currentFacet];
    var visualisation = facets.getFacetExtraInfo(currentFacet, 'visualisation');

    fieldContainer.append(createField(currentFacet, visualisation, facetValues));
    container.append(fieldContainer);

    facets.getFacets().forEach(function(fieldName) {
        createFieldListener(fieldName);
    });

    // only show as many as defined

    var displaySize = facets.getFacetExtraInfo(currentFacet, 'enumDisplayMaxSize');

    if (displaySize) {
      $('.cpo-finder-answer:gt(' + (displaySize - 1) + ')').hide();
    }


  }

  // createField
  function createField(field, elementType, values) {
      var element = null,
          facetLabel = facets.getFacetExtraInfo(field, 'finderQuestion');
      switch(elementType) {
          case 'multiselect':
              element = $('<div></div>');
              var displayMode = facets.getFacetExtraInfo(field, 'display-mode');
              var facetExtraInfo = facets.getFacetExtraInfo(field, 'facetValueExtraInfo');
              if (displayMode == 'imageWithLabel') {
              values.forEach(function(value) {
                  var facetExtraInfoValues = facetExtraInfo[value];
                  var checked = facets.isFacetValueSelected(field, value) ? "checked='checked'" : "";
                  element.append($(checkboxImageWithLabel.replace('%%FieldValue%%', field)
                                        .replace('%%AnswerImage%%', facetExtraInfoValues['image'])
                                        .replace('%%AnswerText%%', facetExtraInfoValues['additional_text'])
                                        .replace('%%AnswerCheckboxName%%', value)
                                        .replace('%%AnswerCheckboxValue%%', value)
                                        .replace('%%AnswerCheckboxChecked%%', checked)));
              });
              }
              else {
                values.forEach(function(value) {
                    var checked = facets.isFacetValueSelected(field, value) ? "checked='checked'" : "";
                    element.append($(checkboxLabelWithoutImage.replace('%%FieldValue%%', field)
                                          .replace('%%AnswerText%%', value)
                                          .replace('%%AnswerCheckboxName%%', value)
                                          .replace('%%AnswerCheckboxValue%%', value)
                                          .replace('%%AnswerCheckboxChecked%%', checked)));
                });
              }
          break;

          case 'radio':
          element = $('<form></form>');
          var displayMode = facets.getFacetExtraInfo(field, 'display-mode');
          var facetExtraInfo = facets.getFacetExtraInfo(field, 'facetValueExtraInfo');
          if (displayMode == 'imageWithLabel') {
          values.forEach(function(value) {
              var facetExtraInfoValues = facetExtraInfo[value];
              var checked = facets.isFacetValueSelected(field, value) ? "checked='checked'" : "";
              element.append($(radioImageWithLabel.replace('%%FieldValue%%', field)
                                    .replace('%%AnswerImage%%', facetExtraInfoValues['image'])
                                    .replace('%%AnswerText%%', facetExtraInfoValues['additional_text'])
                                    .replace('%%AnswerCheckboxName%%', value)
                                    .replace('%%AnswerCheckboxValue%%', value)
                                    .replace('%%AnswerCheckboxChecked%%', checked)));
          });
          }
          else {
            values.forEach(function(value) {
                var facetExtraInfoValues = facetExtraInfo[value];
                var checked = facets.isFacetValueSelected(field, value) ? "checked='checked'" : "";
                element.append($(radioLabelWithoutImage.replace('%%FieldValue%%', field)
                                      .replace('%%AnswerText%%', facetExtraInfoValues['additional_text'])
                                      .replace('%%AnswerCheckboxName%%', value)
                                      .replace('%%AnswerCheckboxValue%%', value)
                                      .replace('%%AnswerCheckboxChecked%%', checked)));
            });
          }
          break;

          case 'dropdown':
              element = $('<select class='+field+' name='+field+'></select>'),
                  selectedValue = selectedValues.hasOwnProperty(field) ? selectedValues[field] : null;
              element.append($('<option value="" disabled selected >Bitte wählen Sie aus.</option>'));
              values.forEach(function(value) {
                  var optionElement = $('<option></option>').attr("value", value).text(value);
                  if(facets.isFacetValueSelected(field, value)) {
                      optionElement.attr('selected', 'selected');
                  }
                  element.append(optionElement);
              });
              element = $('<span><strong>'+facetLabel+'</strong></span><br />').add(element);
              break;
          case 'slider':
              var res = values[0].split("-"),
                  selectedValue = facets.getCurrentSelects(field) === null ? res : facets.getCurrentSelects(field)[0].split("-");
              var activeMin = selectedValue[0],
                  activeMax = selectedValue[1];
              element = $('<div class="range-slider" data-range-slider="true" data-roundPretty="false" data-labelFormat="" data-stepCount="100" data-stepCurve="linear" data-startMin="'+activeMin+'" data-startMax="'+activeMax+'" data-rangeMin="'+res[0]+'" data-rangeMax="'+res[1]+'"></div>');
              element.append($('<input type="hidden" id="min" name="min" data-range-input="min" value="'+res[0]+'"/>'));
              element.append($('<input type="hidden" id="max" name="max" data-range-input="max" value="'+res[1]+'"/>'));
              element.append($('<div class="filter-panel--range-info"> <span class="range-info--min">{s name="ListingFilterRangeFrom" namespace="frontend/listing/listing_actions"}{/s}</span><label class="range-info--label" for="min" data-range-label="min"> '+activeMin+'</label><span class="range-info--max"> {s name="ListingFilterRangeTo" namespace="frontend/listing/listing_actions"}{/s}</span><label class="range-info--label" for="max" data-range-label="max"> '+activeMax+'</label></div>'));
          case 'enumeration':
              element = $('<div><div>');
              values.forEach(function(value) {
                  var checked = facets.isFacetValueSelected(field, value) ? "checked='checked'" : "";
                  element.append( $("<label>"+value+"</label><input class='"+field+"' "+checked+" type='radio' name='"+field+"' value='"+value+"'>"));
              });
              element = $('<span><strong>'+facetLabel+'</strong></span><br />').add(element);
          default:
              break;
      }
      return element;
  }

  // createFieldListener

  function createFieldListener(field) {
      var type = facets.getFacetExtraInfo(field, 'visualisation');
      $("." + field + "_check").on('change', function() {
          if(type == 'radio') {
            facets.removeSelect(field);
              if($(this).is(':checked')) {
                  facets.addSelect(field, $(this).attr('value'));
              } else {
                  facets.removeSelect(field);
              }
          } else if (type == 'multiselect') {
              if($(this).is(':checked')) {
                  facets.addSelect(field, $(this).attr('value'));
              } else {
                  facets.removeSelect(field, $(this).attr('value'));
              }
          } else {
              facets.removeSelect(field);
              facets.addSelect(field, $(this).attr('value'));
          }
          update();
      });
  }

  // update function

  function update() {
      var fields = facets.getUpdatedValues();
      for(var fieldName in fields) {
          var select = $('.'+fieldName).empty();
          var optionValues = fields[fieldName];
          optionValues.forEach(function(optionValue) {
              select.append($('<option></option>').attr("value", optionValue).text(optionValue));
          });
      }
  }

    // selected facets

    var selects = facets.getCurrentSelects();
    jQuery('.cpo-finder-right-criteria').append('<br>');
    if (selects) {
      for(var key in selects){
        if (key != 'bxi_data_owner_expert' && selects[key] != '*') {
          // facet Label
          facetLabel = facets.getFacetLabel(key, lang);
          jQuery('.cpo-finder-right-criteria').append('<b>' + facetLabel + '</b><br>');
          // if there is additional info, use that
          facetExtraInfo = facets.getFacetExtraInfo(key, 'facetValueExtraInfo');
          if (facetExtraInfo) {
            jQuery('.cpo-finder-right-criteria').append('<a class="' + key + '"><p id="' + selects[key] +'" style="margin: 0;">- ' + facetExtraInfo[selects[key]]['additional_text'] + '</p></a>');
          }
          // otherwise use value from DI
          else {
            selects[key].forEach(function (key){
            jQuery('.cpo-finder-right-criteria').append('<a class="' + key + '"><p id="' + key +'" style="margin: 0;">- ' + key + '</p></a>');
            });
          }

        // return to questions
        url = window.location.href;
        jQuery('#' + selects[key]).click(function(){
          var classname = jQuery(this).parent().attr('class');
          var urlParams = new URLSearchParams(window.location.search);
          var entries = urlParams.entries();
          for(pair of entries) {
            pair = pair.join('=')
            if(pair.includes(classname)){
              newUrl = url.replace(pair, '');
              newUrl = url.replace('&&', '&');
              window.location = newUrl;
            }
          }
        });
      }
    }
  }

  // create buttons

  function createButton() {
    // if current facet is set. Create all buttons
    if (currentFacet) {
      var displaySize = facets.getFacetExtraInfo(currentFacet, 'enumDisplayMaxSize');
      var facetValues = facets.getFacetValues(currentFacet)[currentFacet];

      // if maxsize is defined add the show more/less buttons
      if (displaySize && displaySize < facetValues.length) {
        jQuery('.cpo-finder-center-show-more-less').append(additionalButton);
        jQuery('.cpo-finder-center-show-more-less').append(fewerButton);
      }

      // create other buttons
      jQuery('.cpo-finder-button-container').append(backButton);
      jQuery('.cpo-finder-button-container').append(resultsButton);
      jQuery('.cpo-finder-button-container').append(skipButton);
      jQuery('.cpo-finder-button-container-below').append(showProductsButton.replace('%%CurrentScore%%', {$Data.max_score}));

    // otherwise only show back button and show the products

    }else{

      var button = jQuery('[class^=bxCommentButton_]');

      button.click(function(){

        jQuery(this).hide();
        jQuery(this).next().show();

      });

      jQuery('.cpo-finder-button-container').append(backButton);

      jQuery('.cpo-finder-button-container-below').hide();

      var expertResultSentence10 = facets.getFacetValueExtraInfo(expertFieldName, selectedExpert, 'sentence-result-level10');
      var expertResultSentence5  = facets.getFacetValueExtraInfo(expertFieldName, selectedExpert, 'sentence-results-level5');
      var expertResultSentence1  = facets.getFacetValueExtraInfo(expertFieldName, selectedExpert, 'sentence-results-level1');

      var selects = facets.getCurrentSelects();
      var count = 0;

      if (selects) {
        for(var key in selects){
          if (key != 'bxi_data_owner_expert' && selects[key] != '*') {
            count++;
          }
        }
      }
      if (max_score >= 90) { //if highlighted == true
        jQuery('.cpo-finder-center-content-header').append(expertResultSentence10[lang]);
        $('.bx-present').show();
        $('.bx-listing-emotion').show();
      } else {
        jQuery('.cpo-finder-center-content-header').append(expertResultSentence5[lang]);
        $('.bx-listing-emotion').show();
      }
    }
  }
}

  // find button logic
  $('#cpo-finder-results').on('click', function (e) {
        params = facets.getFacetParameters(),
        paramString = '',
        prefix = facets.getParameterPrefix();
        contextPrefix = facets.getContextParameterPrefix();
    params.forEach(function(param, index) {
        if(index > 0) {
            paramString += '&';
        } else {
            paramString += '?'
        }
        if(param.indexOf('=') === -1){
            paramString += param + '=100';
        } else {
            paramString += param;
        }
    });
    window.location.search.substr(1).split("&").forEach(function(param, index) {
        if(param.indexOf(prefix) !== 0 && param.indexOf(contextPrefix) !== 0) {
            bind = ((paramString === '') ? (index > 0 ? '&' : '?') : '&');
            paramString += bind + param;
        }
    });
    window.location = window.location.origin + window.location.pathname + paramString;
});

// show more/less button logic
var displaySize = facets.getFacetExtraInfo(currentFacet, 'enumDisplayMaxSize');
$('.cpo-finder-answer:gt(' + (displaySize - 1) + ')').hide();
$('#cpo-finder-additional').on('click', function (e){
  $('.cpo-finder-answer').show();
  $('#cpo-finder-additional').hide();
  $('#cpo-finder-fewer').show();
});
$('#cpo-finder-fewer').on('click', function (e){
  $('.cpo-finder-answer:gt(' + (displaySize - 1) + ')').hide();
  $('#cpo-finder-fewer').hide();
  $('#cpo-finder-additional').show();
});

// skip button logic
  $('#cpo-finder-skip').on('click', function (e) {
    facets.removeSelect(currentFacet);
    facets.addSelect(currentFacet, '*');
    var params = facets.getFacetParameters(),
        paramString = '',
        prefix = facets.getParameterPrefix();
        contextPrefix = facets.getContextParameterPrefix();
    params.forEach(function(param, index) {
        if(index > 0) {
            paramString += '&';
        } else {
            paramString += '?'
        }
        if(param.indexOf('=') === -1){
            paramString += param + '=100';
        } else {
            paramString += param;
        }
    });
    window.location.search.substr(1).split("&").forEach(function(param, index) {
        if(param.indexOf(prefix) !== 0 && param.indexOf(contextPrefix) !== 0) {
            bind = ((paramString === '') ? (index > 0 ? '&' : '?') : '&');
            paramString += bind + param;
        }
    });
    window.location = url + paramString;
  });

  // back button logic
  $('#cpo-finder-back').on('click', function (e) {
    window.history.back();
  });

// show products button logic

$('#cpo-finder-show-products').on('click', function () {
  var selects = facets.getCurrentSelects();
  var count = 0;
  if (selects) {
    for(var key in selects){
      if (key != 'bxi_data_owner_expert' && selects[key] != '*') {
        count++;
      }
    }
  }
  if (max_score >= 90) {
    $('.bx-present').show();
    $('.bx-listing-emotion').show();
  }
  else {
    $('.bx-listing-emotion').show();
  }
});
