!function(e){function t(n){if(r[n])return r[n].exports;var o=r[n]={i:n,l:!1,exports:{}};return e[n].call(o.exports,o,o.exports,t),o.l=!0,o.exports}var r={};t.m=e,t.c=r,t.d=function(e,r,n){t.o(e,r)||Object.defineProperty(e,r,{configurable:!1,enumerable:!0,get:n})},t.n=function(e){var r=e&&e.__esModule?function(){return e.default}:function(){return e};return t.d(r,"a",r),r},t.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},t.p="",t(t.s=0)}([function(e,t,r){"use strict";Object.defineProperty(t,"__esModule",{value:!0});var n=r(1);r.n(n)},function(e,t){var r=wp.i18n.__;(0,wp.blocks.registerBlockType)("activedemand/form",{title:r(activedemand_vendor+" - Web Form"),icon:"forms",category:"activedemand-blocks",keywords:[r("Web Forms"),r("Form")],attributes:{form_id:{type:"number"}},edit:function(e){function t(t){var r=t.target.querySelector("option:checked");e.setAttributes({form_id:Number(r.value)}),t.preventDefault()}var r={fontSize:"14px",paddingRight:"5px"},n=e.attributes.form_id;return e.setAttributes,wp.element.createElement("div",{className:e.className},wp.element.createElement("label",{style:r},activedemand_vendor+" Form"),wp.element.createElement("select",{value:n,onChange:t},activedemand_forms.map(function(e){return wp.element.createElement("option",{value:e.value},e.label)})))},save:function(){return null}})}]);