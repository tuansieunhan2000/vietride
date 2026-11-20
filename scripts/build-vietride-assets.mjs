import fs from 'node:fs';
import path from 'node:path';
import {createHash} from 'node:crypto';
import {fileURLToPath} from 'node:url';
import * as sass from 'sass';
import CleanCSS from 'clean-css';
import {minify} from 'terser';
const root=path.resolve(path.dirname(fileURLToPath(import.meta.url)),'..');
const cssOnly=process.argv.includes('--css');
const jsOnly=process.argv.includes('--js');
if(!jsOnly){
 const result=sass.compile(path.join(root,'src/sass/custom-style.scss'),{style:'expanded',sourceMap:false});
 fs.writeFileSync(path.join(root,'css/vietride.css'),result.css+'\n');
 for(const name of ['vietride-base','vietride-reference','vietride','vietride-nojs']){
  const input=path.join(root,'css',name+'.css');
  const output=new CleanCSS({level:1,rebase:false}).minify(fs.readFileSync(input,'utf8'));
  if(output.errors.length)throw Error(output.errors.join('\n'));
  fs.writeFileSync(path.join(root,'css',name+'.min.css'),output.styles+'\n');
 }
 const iconFont=fs.readFileSync(path.join(root,'fonts/remix/vietride-icons.woff2'));
 const version=createHash('sha256').update(iconFont).digest('hex').slice(0,12);
 const iconSource=fs.readFileSync(path.join(root,'fonts/remix/vietride-icons.css'),'utf8').replace('vietride-icons.woff2','vietride-icons.woff2?v='+version);
 const icons=new CleanCSS({level:1,rebase:false}).minify(iconSource);
 if(icons.errors.length)throw Error(icons.errors.join('\n'));
 fs.writeFileSync(path.join(root,'fonts/remix/vietride-icons.min.css'),icons.styles+'\n');
 console.log('Built minified VietRide base, reference, design and icon CSS');
}
if(!cssOnly){
 const filename='vietride.js';
 const source=fs.readFileSync(path.join(root,'js',filename),'utf8');
 const output=await minify({[filename]:source},{compress:true,mangle:true,sourceMap:{filename:'vietride.min.js',url:'vietride.min.js.map',includeSources:true}});
 fs.writeFileSync(path.join(root,'js/vietride.min.js'),output.code+'\n');
 fs.writeFileSync(path.join(root,'js/vietride.min.js.map'),output.map+'\n');
 console.log('Built minified VietRide JS with source map');
}
