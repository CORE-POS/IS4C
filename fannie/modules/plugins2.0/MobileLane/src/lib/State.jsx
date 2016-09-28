
const LOGIN     = 1;
const LOGOUT    = 2;
const NAVIGATE  = 3;
const MEMBER    = 4;
const ADDITEM   = 5;
const SETITEMS  = 6;
const RESET     = 99;

function initialState() {
    return {
        loggedIn: false,
        emp: 0,
        reg: 0,
        nav: 'items',
        items: [],
        member: false,
        transComplete: false,
    };
}

function nextState(state, action) {
    switch (action.type) {
        case LOGIN:
            return Object.assign({}, state, {loggedIn: true, emp: action.e, reg: action.r});
        case LOGOUT:
            return Object.assign({}, state, {loggedIn: false, emp: 0, reg: 0, items: []});
        case NAVIGATE:
            return Object.assign({}, state, {nav: a.value}); 
        case MEMBER:
            return Object.assign({}, state, {member: a.value}); 
        case ADDITEM:
            let newitems = state.items.slice(0);
            newitems.push(a.value);
            return Object.assign({}, state, {items: newitems}); 
        case SETITEMS:
            return Object.assign({}, state, {items: a.value}); 
        case RESET:
            return initialState();
        default:
            return state;
    }
}

export { 
    initialState,
    nextState,
    LOGIN,
    LOGOUT,
    NAVIGATE,
    MEMBER,
    ADDITEM,
    SETITEMS,
    RESET,
};

