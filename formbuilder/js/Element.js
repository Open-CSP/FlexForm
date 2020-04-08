/**
 * Element class
 */
class Element {
    constructor(type, attr) {
        this.type = type;
        this.attr = attr;
        this.cols = 0;
        let d = new Date();
        this.id = type + '_' + d.getTime();
        this.dataAttr = {};
    }

    /**
     * get only the input element
     * @return {string}
     */
    input() {
        let result = "";
        if (this.type == 'button' || this.type == 'textarea' || this.type == 'label' || this.type == 'wsemail') {
            result += '<' + this.type;
        } else if ( this.type == 'wstoken' ) {

        } else {
            result = '<input type="' + this.type + '"';
        }

        for (let $let in this.attr) {
            if ($let == 'text') {

            } else {
                result += ' ' + $let + '="' + this.attr[$let] + '"';
            }
        }

        for ( let data in this.dataAttr ) {
            result += ' data-' + data + '="' + this.dataAttr[data] + '"';
        }

        result += '>';
        if (this.type == 'button' && this.attr['text'] != "") {
            result += this.attr['text'] + '</' + this.type + '>';
        } else {
            result += '</' + this.type + '>';
        }
        if ( this.type == 'textarea' || this.type == 'label') {
            result += '</' + this.type +  '>';
        }
        return result;
    }

    /**
     * change an attribute of the element
     * @param key
     * @param value
     */
    changeAttr(key, value) {
        this.attr[key] = value;
    }

    /**
     * remove attribute
     * @param key
     */
    removeAttr(key) {
        let tempAttr = {};
        for ( let $let in this.attr ) {
            if ( $let !== key ) {
                tempAttr[$let] = this.attr[$let];
            }
        }
        this.attr = tempAttr;
    }

    /**
     * get the wiki txt
     * @return {*|string}
     */
    getWikiText() {
        let result = this.getDescription();
        result += '{{#tag:' + this.getWsFormType(true);
        if ( typeof this.attr['text'] !== 'undefined' && this.attr['text'] !== '' && this.type !== 'option') {
            result += '|' + this.attr['text'];
        }
        for (let $let in this.attr) {
            if ($let == 'text' || $let == 'cols' || $let == 'type') {

            } else {
                result += "|" + $let + '=' + this.attr[$let];
            }
        }
        result += '}}';
        return result;
    }

    /**
     *
     * @return {string}
     */
    getWsFormText() {
        let result = this.getDescription();
        result += '<' + this.getWsFormType(false);
        for ( let $attr in this.attr ) {
            if ( $attr == 'text' || $attr == 'cols' || $attr == 'type') {

            } else {
                result += ' ' + $attr + '="' + this.attr[$attr] + '"';
            }
        }
        if ( typeof this.attr['text'] != 'undefined' ) {
            result += '>' + this.attr['text'] + '</' + this.getWsFormType(false, true) + '>';
        } else {
            result += " />";
        }
        return result;
    }

    /**
     *
     * @param wikiNotation
     * @param isForClosing
     * @return {string}
     */
    getWsFormType(wikiNotation = false, isForClosing = false) {
        switch (this.type) {
            case 'label':
                return 'wslabel';
            case 'select':
                if ( wikiNotation ) return 'wsselect|';
                return 'wsselect';
            case 'wscreate':
                if ( wikiNotation ) return 'wscreate|';
                return 'wscreate';
            case 'wsedit':
                if ( wikiNotation ) return 'wsedit|';
                return 'wsedit';
            case 'wsemail':
                if ( wikiNotation ) return 'wsemail|';
                return 'wsemail';
            case 'form':
                return 'wsform';
            case 'option':
                if ( wikiNotation ) return `wsfield|${this.attr['text']}|type=${this.type}`;
                if ( isForClosing ) return 'wsfield';
                return 'wsfield type="'+this.type+'"';
            default:
                if ( wikiNotation ) return 'wsfield||type='+this.type;
                if ( isForClosing ) return 'wsfield';
                return 'wsfield type="'+this.type+'"';
        }
    }

    /**
     * get the html element including container and input
     * @return {HTMLElement}
     */
    getElement() {
        let elm = document.createElement('div');
        let colNr = this.getColNr();
        let deleteElement = document.createElement('div');
        $(deleteElement).addClass('remove-element');
        deleteElement.textContent = 'x';
        deleteElement.style = 'z-index:999';
        $(elm).addClass('col-md-'+colNr);
        $(elm).addClass('ws-form-col');
        elm.dataset['id'] = this.id;
        elm.id = this.id;
        $(deleteElement).on('click', function() {
            removeElement(elm.id);
        });
        $(elm).append(this.input());
        $(elm).append(deleteElement);
        return elm;
    }

    /**
     * get the col number of the container
     * @return Integer
     */
    getColNr() {
        if ( this.cols != 0 ) {
            return this.cols;
        } else if ( this.type == "label" && this.cols == 0) {
            return 3;
        } else if ( this.type == 'wscreate' || this.type == 'wsedit' ) {
            return 12;
        } else {
            return 9;
        }
    }

    /**
     * set the description for the element
     * @param txt
     */
    setDescription(txt) {
        this.description = txt;
    }

    /**
     * set the cols for the container
     * @param cols
     */
    setCols(cols) {
        this.cols = cols;
    }

    /**
     * get the description
     * @return {string}
     */
    getDescription() {
        if (this.description) {
            return '<!-- ' + this.description + ' -->';
        }
        return '';
    }

    /**
     * add data attr to element input
     * @param name
     * @param value
     */
    addDataAttr(name, value) {
        this.dataAttr[name] = value;
    }

    /**
     *
     * @param newID
     */
    setId(newID) {
        this.id = newID;
    }

    /**
     *
     * @param attrObj
     */
    setAttr(attrObj) {
        let newAttr = {};
        for ( let $attr in attrObj ) {
            newAttr[$attr] = attrObj[$attr];
        }
        this.attr = newAttr;
    }

    /**
     *
     * @param dataAttrObj
     */
    setDataAttr(dataAttrObj) {
        let newAttr = {};
        for ( let $attr in dataAttrObj ) {
            newAttr[$attr] = dataAttrObj[$attr];
        }
        this.dataAttr = newAttr;
    }
}

/**
 * @class FormElement
 */
class FormElement extends Element {
    constructor(type, attr) {
        super(type, attr);
        this.name = "";
    }

    /**
     * get input of the form
     * @return {string}
     */
    input() {
        let form = '<form';

        for ( let $attr in  this.attr ) {
            form += ' ' + $attr + '="' + this.attr[$attr] + '"';
        }
        form += '></form>';
        return form;
    }

    /**
     * get the form element -> input parsed to html object
     * @return {*}
     */
    getElement() {
        return $(this.input())[0];
    }

    /**
     * get the wsform notation of the form
     * @return {string}
     */
    getWsFormText() {
        return super.getWsFormText();
    }

    /**
     * get the first part of the wsform notation
     * @returns {string}
     */
    getWsFormStartText() {
        let form = '<wsform';

        for ( let $attr in this.attr ) {
            form += ' ' + $attr + '="' + this.attr[$attr] + '"';
        }
        form += '>';
        return form;
    }

    /**
     * get the last part of the wsform notation
     * @returns {string}
     */
    getWsFormEndText() {
        return '</wsform>';
    }

    /**
     * get the first part of the mediawiki notation
     * @returns {string}
     */
    getWikiStartText() {
        return '{{#tag:wsform|';
    }

    /**
     * get the last part of the mediawiki notation
     * @returns {string}
     */
    getWikiEndText() {
        let form = '';

        for ( let $attr in this.attr ) {
            form += '|' + $attr + '=' + this.attr[$attr];
        }

        form += '}}';
        return form;
    }

    /**
     * get the mediawiki notation of the form
     * @return {*|string}
     */
    getWikiText() {
        return super.getWikiText();
    }
}

/**
 * Select class
 * @extends Element
 */
class Select extends Element {
    constructor(type, attr) {
        super(type, attr);
        this.options = {};
        this.countOptions = 0;
    }

    /**
     * add an Option class to the select
     * @param value
     * @param txt
     * @param selected
     */
    addOption(value, txt, selected = false) {
        let attr = {};
        this.countOptions++;
        if ( value ) {
            attr['value'] = value;
        }
        if ( txt ) {
            attr['text'] = txt;
        }
        if ( selected ) {
            attr['selected'] = "selected";
        }
        this.options[this.countOptions] = new Option('option', attr, this.countOptions);
    }

    /**
     * remove option from select
     * @param nr
     */
    removeOption(nr) {
        delete this.options[nr];
        this.rearrangeOptionsList();
    }

    /**
     * change the option attribute
     * @param nr
     * @param key
     * @param value
     */
    changeAttrOption(nr, key, value) {
        this.options[nr].changeAttr(key, value);
    }

    /**
     * rearrange the option list
     */
    rearrangeOptionsList() {
        let count = 1;
        let tempList = {};
        for ( let $opt in this.options ) {
            tempList[count] = this.options[$opt];
            this.options[$opt].setNr(count);
            count++;
        }
        this.options = tempList;
    }

    /**
     * get options list
     * @return {Array}
     */
    showOptionsList() {
        return this.options;
    }

    /**
     * get the input of the select with options
     * @return {string}
     */
    input() {
        let result = '<' + this.type;
        for (let $var in this.attr) {
            result += ' ' + $var + '="' + this.attr[$var] + '"';
        }
        for ( let $data in this.dataAttr ) {
            result += ' data-' + $data + '="' + this.dataAttr[$data] + '"';
        }
        result += '>';
        for (let i in this.options) {
            result += this.options[i].input();
        }
        result += '</' + this.type + '>';
        return result;
    }

    getWsFormText() {
        let result = this.getDescription();
        result += '<' + this.getWsFormType(false);
        for ( let $attr in this.attr ) {
            if ( $attr == 'text' || $attr == 'cols' || $attr == 'type') {

            } else {
                result += ' ' + $attr + '="' + this.attr[$attr] + '"';
            }
        }
        result += '>';
        for ( let i in this.options ) {
            result += '\n\t\t\t\t';
            result += this.options[i].getWsFormText();
        }
        result += '\n\t\t\t';
        result += '</' + this.getWsFormType(false) + '>';
        return result;
    }

    getWikiText() {
        let result = this.getDescription();
        result += '{{#tag:' + this.getWsFormType(true);
        for ( let i in this.options ) {
            result += this.options[i].getWikiText();
        }
        for (let $let in this.attr) {
            if ($let == 'text' || $let == 'cols' || $let == 'type') {

            } else {
                result += "|" + $let + '=' + this.attr[$let];
            }
        }
        result += '}}';
        return result;
    }

    /**
     * change the order of the options in the select
     * @param order
     */
    changeOptionsOrder(order) {
        let tempList = {};
        let i = 0;
        for ( let $opt in order ) {
            i++;
            let index = parseInt(order[$opt]);
            tempList[i] = this.options[index];
        }
        this.countOptions = i;
        this.options = tempList;
    }

    /**
     *
     * @param obj
     */
    setOptions(obj) {
        let newObj = {};
        for ( let $key in obj ) {
            let attr = obj[$key]['attr'];
            /*let orderNr = obj[$key]['orderNr'];
            let type = obj[$key]['type'];
            console.log(type, orderNr, attr);
            let option = new Option(type, attr);
            option.setNr(orderNr);
            newObj[orderNr] = option;*/

            let value = attr['value'];
            let text = attr['text'];
            this.addOption(value, text);
        }
        //this.options = newObj;
    }

    /**
     * set the options of the selectbox by params obj
     * @param obj
     */
    setOptionsThroughOptionObj(obj) {
        let newObj = {};
        for ( let $obj in obj ) {
            newObj[$obj] = obj[$obj];
        }
        this.options = newObj;
    }
}


/**
 * Option class
 * @extends Element
 */
class Option extends Element {
    constructor(type, attr, nr = -1) {
        super(type, attr);
        this.orderNr = nr;
    }

    /**
     * get the input of the option
     * @return {string}
     */
    input() {
        let result = '<' + this.type;
        for (let $var in this.attr) {
            if ($var == 'text') {

            } else {
                result += ' ' + $var + '="' + this.attr[$var] + '"';
            }

        }
        result += '>' + this.attr['text'] + '</' + this.type + '>';
        return result;
    }

    /**
     * set the index number in the select
     * @param nr
     */
    setNr(nr) {
        this.orderNr = nr;
    }

}

/**
 * Checkbox class
 * @extends Element
 */
class Checkbox extends Element {
    constructor(type, attr) {
        super(type,attr);
    }

    /**
     * get the input of the checkbox
     * @return {string}
     */
    input() {
        let result = '<' + this.type;
        for (let $var in this.attr) {
            if ($var == 'text') {

            } else {
                result += ' ' + $var + '="' + this.attr[$var] + '"';
            }

        }
        for ( let $data in this.dataAttr ) {
            result += ' data-' + $data + '="' + this.dataAttr[$data] + '"';
        }
        result += '>' + this.attr['text'] + '</' + this.type + '>';
        return result;
    }
}

/**
 * Label class
 * @extends Element
 */
class Label extends Element {
    constructor(type, attr) {
        super(type, attr);
    }

    /**
     * get the input of the label
     * @return {string}
     */
    input() {
        let result = '<' + this.type;
        for (let $let in this.attr) {
            if ($let == 'text') {

            } else {
                result += ' ' + $let + '="' + this.attr[$let] + '"';
            }

        }
        for ( let $data in this.dataAttr ) {
            result += ' data-' + $data + '="' + this.dataAttr[$data] + '"';
        }
        result += '>' + this.attr['text'] + '</' + this.type + '>';
        return result;
    }
}

/**
 * Wscreate class
 * @extends Element
 */
class Wscreate extends Element {
    constructor(type, attr) {
        super(type, attr);
    }

    /**
     * get the input of the Wscreate
     * @returns {string}
     */
    input() {
        let result = '<' + this.type;
        for (let $let in this.attr) {
            if ( $let === 'type' || $let === 'cols') {

            } else {
                result += ' ' + $let + '="' + this.attr[$let] + '"';
            }
        }
        for ( let $data in this.dataAttr ) {
            result += ' data-' + $data + '="' + this.dataAttr[$data] + '"';
        }
        result += ' />';
        return result;
    }

    /**
     * get the element of the Wscreate
     * @returns {HTMLDivElement}
     */
    getElement() {
        let elm = document.createElement('div');
        let colNr = this.getColNr();
        let deleteElement = document.createElement('div');
        $(deleteElement).addClass('remove-element');
        deleteElement.textContent = 'x';
        deleteElement.style = 'z-index:999';
        $(elm).addClass('col-md-'+12);
        $(elm).addClass('ws-form-col');
        elm.dataset['id'] = this.id;
        elm.id = this.id;
        $(deleteElement).on('click', function() {
            removeElement(elm.id);
        });
        $(elm).text(this.input());
        $(elm).append(deleteElement);
        return elm;
    }
}

/**
 * Wsedit class
 * @extends Element
 */
class Wsedit extends Element {
    constructor(type, attr) {
        super(type, attr);
    }

    /**
     * get the input of the Wsedit
     * @returns {string}
     */
    input() {
        let result = '<' + this.type;
        for (let $let in this.attr) {
            if ( $let === 'type' || $let === 'cols') {

            } else {
                result += ' ' + $let + '="' + this.attr[$let] + '"';
            }
        }
        for ( let $data in this.dataAttr ) {
            result += ' data-' + $data + '="' + this.dataAttr[$data] + '"';
        }
        result += ' />';
        return result;
    }

    /**
     * get the element of the Wsedit
     * @returns {HTMLDivElement}
     */
    getElement() {
        let elm = document.createElement('div');
        let colNr = this.getColNr();
        let deleteElement = document.createElement('div');
        $(deleteElement).addClass('remove-element');
        deleteElement.textContent = 'x';
        deleteElement.style = 'z-index:999';
        $(elm).addClass('col-md-'+12);
        $(elm).addClass('ws-form-col');
        elm.dataset['id'] = this.id;
        elm.id = this.id;
        $(deleteElement).on('click', function() {
            removeElement(elm.id);
        });
        $(elm).text(this.input());
        $(elm).append(deleteElement);
        return elm;
    }
}

class Wsemail extends Element {
    constructor(type, attr) {
        super(type, attr);
    }

    /**
     * get the input of the Wsedit
     * @returns {string}
     */
    input() {
        let result = '<input type="' + this.type + '"';
        for (let $let in this.attr) {
            if ( $let === 'type' || $let === 'cols') {

            } else {
                result += ' ' + $let + '="' + this.attr[$let] + '"';
            }
        }
        for ( let $data in this.dataAttr ) {
            result += ' data-' + $data + '="' + this.dataAttr[$data] + '"';
        }
        result += '/>';
        return result;
    }

    /**
     * get the element of the Wsedit
     * @returns {HTMLDivElement}
     */
    getElement() {
        let elm = document.createElement('div');
        let colNr = this.getColNr();
        let deleteElement = document.createElement('div');
        $(deleteElement).addClass('remove-element');
        deleteElement.textContent = 'x';
        deleteElement.style = 'z-index:999';
        $(elm).addClass('col-md-'+this.getColNr());
        $(elm).addClass('ws-form-col');
        elm.dataset['id'] = this.id;
        elm.id = this.id;
        $(deleteElement).on('click', function() {
            removeElement(elm.id);
        });

        $(elm).append(this.input());
        $(elm).append(deleteElement);
        return elm;
    }
}

/**
 * Available Attributes class
 * @static functions
 */
class AvailableAttributes {
    /**
     * get the available input attributes
     * @return {{array: string[], name: {inputtype: string}, placeholder: {inputtype: string}, id: {inputtype: string}, type: {options: string[], inputtype: string}, cols: {options: number[], inputtype: string}, class: {inputtype: string}, required: {inputtype: string}}}
     */
    static getInputAttr() {
        return {
            'array': [
                "cols",
                "type",
                "placeholder",
                "required",
                "id",
                "class",
                "name",
                "value"
            ],
            'cols': {
                options : [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12],
                inputtype: 'select'
            },
            'type': {
                options: AvailableAttributes.getDefaultTypes(),
                inputtype: 'select'
            },
            'placeholder' : {
                inputtype: 'text'
            },
            'id': {
                inputtype: 'text'
            },
            'class': {
                inputtype: 'text'
            },
            'name': {
                inputtype: 'text'
            },
            'value': {
                inputtype: 'text'
            },
            'required':{
                inputtype: 'checkbox'
            }
        };
    }

    /**
     * return the available form attributes
     * @returns {{enctype: {inputtype: string}, extension: {inputtype: string}, method: {inputtype: string}, array: string[], changetrigger: {inputtype: string}, formname: {inputtype: string}, action: {inputtype: string}, loadscript: {inputtype: string}}}
     */
    static getFormAttr() {
        return {
            'array': [
                "action",
                "enctype",
                "formname",
                "loadscript",
                "changetrigger",
                "extension",
                "mwfollow",
                "mwreturn",
                "formtarget",
                "handleQuery",
                "messageonsuccess",
                "no_submit_on_return",
                "recaptcha-v3-action",
                "restrictions"
            ],
            "action": {
                inputtype: 'select',
                options: ['addToWiki', 'get', 'mail']
            },
            "mwfollow": {
                inputtype: "text"
            },
            "mwreturn": {
              inputtype: "text"  
            },
            "enctype": {
                inputtype: "text"
            },
            "formname": {
                inputtype: "text"
            },
            "loadscript": {
                inputtype: "text"
            },
            "changetrigger": {
                inputtype: "text"
            },
            "extension": {
                inputtype: "text"
            },
            "formtarget": {
                inputtype: "text"
            },
            "handleQuery": {
                inputtype: "text"
            },
            "messageonsuccess": {
                inputtype: "text"
            },
            "no_submit_on_return": {
                inputtype: "text"
            },
            "recaptcha-v3-action": {
                inputtype: "text"
            },
            "restrictions": {
                inputtype: "text"
            }
        };
    }

    /**
     * get the label available attributes
     * @return {{array: string[], for: {inputtype: string}, text: {inputtype: string}, cols: {options: number[], inputtype: string}, class: {inputtype: string}}}
     */
    static getLabelAttr() {
        return {
            'array': [
                "cols",
                "for",
                "text",
                "class"
            ],
            'cols': {
                options : [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12],
                inputtype: 'select'
            },
            'for': {
                inputtype: 'text'
            },
            'class': {
                inputtype: 'text'
            },
            'text': {
                inputtype: 'text'
            }
        };
    }

    /**
     * get the select available attributes
     * @return {{array: string[], options: {inputtype: string}, id: {inputtype: string}, cols: {options: number[], inputtype: string}, class: {inputtype: string}, required: {inputtype: string}}}
     */
    static getSelectAttr() {
        return {
            'array': [
                "cols",
                "id",
                "options",
                "class",
                "required",
                "name"
            ],
            'cols': {
                options : [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12],
                inputtype: 'select'
            },
            'id': {
                inputtype: 'text'
            },
            'class': {
                inputtype: 'text'
            },
            'required': {
                inputtype: 'checkbox'
            },
            'options': {
                inputtype: 'optionsList'
            },
            'name': {
                inputtype: 'text'
            }
        };
    }

    /**
     * get the available file attributes
     * @return {{array: string[], options: {inputtype: string}, id: {inputtype: string}, cols: {options: number[], inputtype: string}, class: {inputtype: string}, required: {inputtype: string}}}
     */
    static getFileAttr() {
        return {
            'array': [
                "cols",
                "id",
                "name",
                "pagecontent",
                "class",
                "required",
                "target",
                "presentor",
                "force",
                "error_id",
                "verbose_id"
            ],
            'cols': {
                options : [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12],
                inputtype: 'select'
            },
            'id': {
                inputtype: 'text'
            },
            'name': {
                inputtype: 'text'
            },
            'class': {
                inputtype: 'text'
            },
            'required': {
                inputtype: 'checkbox'
            },
            'pagecontent': {
                inputtype: 'text'
            },
            "target": {
                inputtype: 'text'
            },
            "presentor": {
                inputtype: 'text'
            },
            "force": {
                inputtype: 'text'
            },
            "error_id": {
                inputtype: 'text'
            },
            "verbose_id": {
                inputtype: 'text'
            }
        };
    }

    /**
     * get the available button attributes
     * @return {{array: string[], id: {inputtype: string}, text: {inputtype: string}, cols: {options: number[], inputtype: string}, class: {inputtype: string}}}
     */
    static getButtonAttr() {
        return {
            'array': [
                "cols",
                "id",
                "text",
                "class",
                "value",
                "beforecallback",
                "mwidentifier",
                "callback"
            ],
            'cols': {
                options : [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12],
                inputtype: 'select'
            },
            'id': {
                inputtype: 'text'
            },
            'class': {
                inputtype: 'text'
            },
            'text': {
                inputtype: 'text'
            },
            'value': {
                inputtype: 'text'
            },
            'beforecallback': {
                inputtype: 'text'
            },
            'mwidentifier': {
                inputtype: 'text'
            },
            'callback': {
                inputtype: 'text'
            }
        };
    }

    /**
     * get the default available types
     * @return {string[]}
     */
    static getDefaultTypes() {
        return [
            "text",
            "password",
            "radio",
            "checkbox",
            "textarea",
            "email",
            "number",
            "date"
        ];
    }

    /**
     * get the attributes of the Wscreate element
     * @returns {{array: string[], mwfields: {inputtype: string}, mwfollow: {inputtype: string}, mwtemplate: {inputtype: string}, mwwrite: {inputtype: string}, mwoption: {inputtype: string}}}
     */
    static getWSCreate() {
        return {
            'array': [
                "mwtemplate",
                "mwwrite",
                "mwfollow",
                "mwoption",
                "mwfields",
                "mwleadingzero"
            ],
            'mwtemplate': {
                inputtype: 'text'
            },
            'mwwrite': {
                inputtype: 'text'
            },
            'mwfollow': {
                inputtype: 'text'
            },
            'mwoption': {
                inputtype: 'text'
            },
            'mwfields': {
                inputtype: 'text'
            },
            'mwleadingzero': {
                inputtype: 'text'
            }
        };
    }

    /**
     * get the attributes of the Wsedit element
     * @returns {{template: {inputtype: string}, array: string[], formfield: {inputtype: string}, usefield: {inputtype: string}, value: {inputtype: string}, target: {inputtype: string}}}
     */
    static getWSEdit() {
        return {
            'array': [
                "target",
                "template",
                "formfield",
                "usefield",
                "value"
            ],
            'target': {
                inputtype: 'text'
            },
            'template': {
                inputtype: 'text'
            },
            'formfield': {
                inputtype: 'text'
            },
            'usefield': {
                inputtype: 'text'
            },
            'value': {
                inputtype: 'text'
            }
        };
    }

    /**
     * get the available wsemail attributes
     * @returns {{cc: {inputtype: string}, template: {inputtype: string}, bcc: {inputtype: string}, array: string[], footer: {inputtype: string}, subject: {inputtype: string}, header: {inputtype: string}, from: {inputtype: string}, html: {inputtype: string}, to: {inputtype: string}, cols: {options: number[], inputtype: string}}}
     */
    static getWSEmail() {
        return {
            'array': [
                "cols",
                "to",
                "cc",
                "bcc",
                "from",
                "subject",
                "template",
                "html",
                "footer",
                "header",
                "job"
            ],
            'cols': {
                options : [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12],
                inputtype: 'select'
            },
            'to': {
                inputtype: 'email'
            },
            'cc': {
                inputtype: 'email'
            },
            'bcc': {
                inputtype: 'text'
            },
            'from': {
                inputtype: 'email'
            },
            'subject': {
                inputtype: 'text'
            },
            'template': {
                inputtype: 'text'
            },
            'html': {
                inputtype: 'text'
            },
            'footer': {
                inputtype: 'text'
            },
            'header': {
                inputtype: 'text'
            },
            "job": {
                inputtype: 'text'
            }
        };
    }

    /**
     * get the doc link for a type or attribute
     * @returns {{handleQuery: string, extension: string, color: string, submit: string, messageonsuccess: string, wsemail: string, range: string, textarea: string, no_submit_on_return: string, required: string, wsfield: string, wsselect: string, general: string, number: string, password: string, file: string, text: string, email: string, wscreate: string, mwidentifier: string, restrictions: string, url: string, formtarget: string, wsedit: string, phone: string, wsform: string, changetrigger: string, "recaptcha-v3-action": string, callback: string, wslabel: string, job: string, beforecallback: string, loadscript: string}}
     */
    static links() {
        return {
            "general": '/index.php/Special:WSForm/Docs',
            "wscreate": '/wscreate_wscreate',
            "wsedit": '/wsedit_wsedit',
            "wsemail": '/wsemail_email-no-job',
            "job": '/wsemail_jobs',
            "text": '/wsfield_Input-text',
            "color": '/wsfield_input-color',
            "email": '/wsfield_input-email',
            "file": '/wsfield_input-file',
            "number": '/wsfield_input-number',
            "password": '/wsfield_input-password',
            "phone": '/wsfield_input-phone',
            "range": '/wsfield_input-range',
            "textarea": '/wsfield_input-textarea',
            "url": '/wsfield_input-url',
            "required": '/wsfield_required',
            "submit": '/wsfield_submit-form-traditional',
            "beforecallback": '/wsfield_submit-beforecallback',
            "callback": '/wsfield_sumbit-form-ajax-with-callback',
            "mwidentifier": '/wsfield_submit-form-ajax',
            "wsfield": '/wsfield_wsfield',
            "changetrigger": '/wsform_changetrigger',
            "extension": '/wsform_extension',
            "formtarget": '/wsform_formtarget',
            "handleQuery": '/wsform_handleQuery',
            "loadscript": '/wsform_loadscript',
            "messageonsuccess": '/wsform_messageonsuccess',
            "no_submit_on_return": '/wsform_no_submit_on_return',
            "recaptcha-v3-action": '/wsform_recaptcha-v3-action',
            "restrictions": '/wsform_restrictions',
            "wsform": '/wsform_wsform',
            "wsselect": '/wsselect_general',
            "wslabel": '/wslabel_wslabel'
        };
    }
}
