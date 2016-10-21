function formAdd(form_selector, input_name, input_value)
{
    var inp = $('<input />')
        .attr('name', input_name)
        .attr('type', 'hidden')
        .val(input_value);
    $(form_selector).append(inp);
}

