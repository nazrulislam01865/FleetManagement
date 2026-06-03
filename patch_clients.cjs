const fs = require('fs');

let code = fs.readFileSync('public/js/fleetman.js', 'utf8');

// Inside initClients(), modify addContact
code = code.replace(
    /<div class="field"><label>Contact Person Name <span class="req">\*<\/span><\/label><input class="clientContactName" placeholder="Example: Md. Karim" value="\$\{escapeHtml\(row\.name\|\|''\)\}"><\/div>/,
    \`<div class="field"><label>Contact Person Name <span class="req">*</span></label><input class="clientContactName" placeholder="Example: Md. Karim" value="\${escapeHtml(row.name||'')}"></div><div class="field"><label>Contact Type</label><select class="clientContactType">\${((window.FLEETMAN.options||{}).client_contact_methods||[]).map(t => '<option value="'+escapeHtml(t)+'" '+(row.type===t?'selected':'')+'>'+escapeHtml(t)+'</option>').join('')}</select></div>\`
);

// Inside initClients(), modify collect
code = code.replace(
    /return \{name:\$\('\.clientContactName',row\)\?\.value\.trim\(\)\|\|'',role:\$\('\.clientContactRole',row\)\?\.value\.trim\(\)\|\|'',phone:\$\('\.clientContactPhone',row\)\?\.value\.trim\(\)\|\|'',whatsapp:meta\.includes\('@'\)\? '':meta,email:meta\.includes\('@'\)\?meta:''\}; \}\)\.filter\(\(c\)=>c\.name\|\|c\.phone\|\|c\.role\|\|c\.whatsapp\|\|c\.email\);/,
    \`return {name:$('.clientContactName',row)?.value.trim()||'',role:$('.clientContactRole',row)?.value.trim()||'',phone:$('.clientContactPhone',row)?.value.trim()||'',type:$('.clientContactType',row)?.value||'',whatsapp:meta.includes('@')?'':meta,email:meta.includes('@')?meta:''}; }).filter((c)=>c.name||c.phone||c.role||c.whatsapp||c.email||c.type);\`
);

// Inside rowHtml, show the type
code = code.replace(
    /<td><b>\$\{escapeHtml\(main\.name\|\|'-'\)\}<\/b><br><small>\$\{escapeHtml\(main\.phone\|\|''\)\}\$\{\(row\.contacts\|\|\[\]\)\.length>1\?' · \+'\+\(\(row\.contacts\|\|\[\]\)\.length-1\)\+' more':''\}<\/small><\/td>/,
    \`<td><b>\${escapeHtml(main.name||'-')}</b> <span class="badge soft" style="font-size:10px">\${escapeHtml(main.type||'')}</span><br><small>\${escapeHtml(main.phone||'')}\${(row.contacts||[]).length>1?' · +'+((row.contacts||[]).length-1)+' more':''}</small></td>\`
);

fs.writeFileSync('public/js/fleetman.js', code);
console.log('Done!');
