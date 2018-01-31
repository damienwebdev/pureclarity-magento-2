define(['jquery'], function ($) {

    // Before initialise, check we're active
    if (!pureclarityConfig.state.isActive) return;

    // If search or category page prepare elements
    if (pureclarityConfig.search.isClientSearch && pureclarityConfig.search.DOMSelector != ""){
        var pcContainer = document.createElement('div');
        var wrapper = document.createElement('div');
        $(wrapper).addClass('pureclarity-wrapper');
        $(pcContainer).addClass('pureclarity-container').attr("data-pureclarity", pureclarityConfig.search.dataValue);
        $(pureclarityConfig.search.DOMSelector).wrap(wrapper);
        $(".pureclarity-wrapper").append(pcContainer);
    }

    // Initialise PureClarity
    (function(w, d, s, u, f) {
        w['PureClarityObject'] = f;w[f] = w[f] || function() {(w[f].q = w[f].q || []).push(arguments)}
        var p = d.createElement(s), h = d.getElementsByTagName(s)[0];
        p.src = u;p.async=1;h.parentNode.insertBefore(p, h);
    })(window, document, 'script', window.pureclarityConfig.apiUrl, '_pc');

    // Execute tracking events
    if (!pureclarityConfig.state.serversideMode){
        _pc('currency', pureclarityConfig.currency );
        _pc('page_view');

        if (pureclarityConfig.product){
            _pc("product_view", { id: pureclarityConfig.product.Id });
        }
        
        if (pureclarityConfig.state.isLogout){
            _pc('customer_logout');
        }

        if (pureclarityConfig.order){
            _pc('order:addTrans', pureclarityConfig.order.transaction);
            for(var i=0; i<pureclarityConfig.order.items.length; i++){
                _pc('order:addItem', pureclarityConfig.order.items[i]);
            }
            _pc('order:track');
        }
    }
    else {
        _pc('set_cache_filter', { _size: 2000, requesttype: "both" });
    }

    

    return {
        push: function(event, value){
            if (window['PureClarityObject'] && typeof window['PureClarityObject'].push === 'function') {
                window['PureClarityObject'].push([event, value]);
            }
            else if (typeof _pc === 'function') {
                _pc(event, value);
            }
        },
        sectionUpdated(dataId, section){
            var cookieSections = $.cookieStorage.get('section_data_ids');
            return dataId && cookieSections &&
                   cookieSections[section] != dataId;
        }
    }
});