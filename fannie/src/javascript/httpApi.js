
var CoreHttpApi = (function() {
    var mod = {};
    var url = '';

    function baseRequest(method, params) {
        return {
            jsonrpc: '2.0',
            id: new Date().getTime(),
            method: method,
            params: params
        };
    };

    function entityGetSet(submethod, entity, columns) {
        var wsClass = '\\COREPOS\\Fannie\\API\\webservices\\FannieEntity';
        var params = {
            entity: entity,
            submethod: submethod,
            columns: columns
        };

        return baseRequest(wsClass, params);
    }

    mod.getEntity = function(entity, columns) {
        return entityGetSet('get', entity, columns);
    };

    mod.setEntity = function(entity, columns) {
        return entityGetSet('set', entity, columns);
    };

    mod.getMember = function(id) {
        var wsClass = '\\COREPOS\\Fannie\\API\\webservices\\FannieMember';
        var params = { cardNo: id, method: 'get' };

        return baseRequest(wsClass, params);
    };

    mod.setMember = function(id, member) {
        var wsClass = '\\COREPOS\\Fannie\\API\\webservices\\FannieMember';
        var params = { cardNo: id, method: 'set', member: member };

        return baseRequest(wsClass, params);
    };

    mod.setURL = function(u) {
        url = u;
    };

    mod.process = function(req) {
        return fetch(url, {
            method: 'POST',
            headers { 'Content-Type' : 'application/json' },
            body: JSON.stringify(req)
        });
    };

    return mod;

})();
