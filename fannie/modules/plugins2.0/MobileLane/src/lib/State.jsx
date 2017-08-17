
export const LOGIN     = 1;
export const LOGOUT    = 2;
export const NAVIGATE  = 3;
export const MEMBER    = 4;
export const ADDITEM   = 5;
export const SETITEMS  = 6;
export const RESET     = 99;

export function initialState() {
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

export function nextState(state, action) {
    switch (action.type) {
        case LOGIN:
            return Object.assign({}, state, {loggedIn: true, emp: action.e, reg: action.r});
        case LOGOUT:
            return Object.assign({}, state, {loggedIn: false, emp: 0, reg: 0, items: []});
        case NAVIGATE:
            return Object.assign({}, state, {nav: action.value});
        case MEMBER:
            return Object.assign({}, state, {member: action.value});
        case ADDITEM:
            let newitems = state.items.slice(0);
            newitems.push(a.value);
            return Object.assign({}, state, {items: newitems});
        case SETITEMS:
            return Object.assign({}, state, {items: action.value});
        case RESET:
            return initialState();
        default:
            return state;
    }
}

