const { JSDOM } = require('jsdom');
const dom = new JSDOM(`<!DOCTYPE html><p>Hello</p>`);
const window = dom.window;
const document = window.document;
let count = 0;
document.addEventListener('DOMContentLoaded', () => { count++; });
document.dispatchEvent(new window.Event('DOMContentLoaded'));
document.dispatchEvent(new window.Event('DOMContentLoaded'));
console.log("Count:", count);
