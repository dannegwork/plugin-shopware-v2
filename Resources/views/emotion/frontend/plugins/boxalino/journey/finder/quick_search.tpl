{block name="frontend_quick_finder_content"}
    <div class="wrapper" style="margin:0">

        <div class="quickFinder" style="">

            <div class="quickFinderContent" style="">

            </div>

            <div class="quickFinderButton" style="">

            </div>

        </div>

    </div>

{/block}

{block name="frontend_product_finder_script"}

    <script>

        var json =  {$json_facets},
            lang  = '{$locale}',
            facets = new bxFacets(),
            selectedValues = {};
        facets.init(json);

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

        // image for default expert & Intro

        var defaultExpertFirstName = facets.getFacetValueExtraInfo(expertFieldName, defaultExpert, 'first-name');
        var defaultExpertLastName  = facets.getFacetValueExtraInfo(expertFieldName, defaultExpert, 'last-name');
        var persona                = facets.getFacetValueExtraInfo(expertFieldName, defaultExpert, 'persona');
        var defaultExpertQuestionImage = facets.getFacetValueExtraInfo(expertFieldName, defaultExpert, 'question-img');
        var quickFinderIntro = facets.getFacetExtraInfo(expertFieldName, 'quickFinderIntro');

        // append default expert to template

        jQuery('.quickFinderContent').append('<img src="https://' + defaultExpertQuestionImage + '" alt="" style="width:90%;border-radius: 50%;right:0;left:0;margin-left:auto;margin-right:auto;"><div class="text"><p style="font-size: 1.2rem;text-align:center;">' + quickFinderIntro + '</p></div>');

        createButton();

        function createButton() {
            $('.quickFinderButton').append($('<button id="b-find" style="background-color: #993366; border:none; color:white; text-align:center;width:100%;font-size:1.2rem;">ZUM PRODUKTEFINDER</button>'));
            $('#b-find').on('click', function (e) {
                var urlString = '{url controller=cat sCategory=$Data.cpo_finder_link}',
                    params = facets.getFacetParameters();
                params.forEach(function(param, index) {

                    if(index > 0) {
                        urlString += '&';
                    } else {
                        urlString += '?'
                    }
                    if(param.indexOf('=') === -1){
                        urlString += param + '=100';
                    } else {
                        urlString += param;
                    }
                });
                window.location = urlString;
            });
        }

    </script>

{/block}
