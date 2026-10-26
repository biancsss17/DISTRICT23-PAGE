const {chromium}=require('C:/Users/User/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright');
(async()=>{const browser=await chromium.launch({executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe',headless:true});
for(const width of [1440,390]){
 const page=await browser.newPage({viewport:{width,height:900},reducedMotion:'reduce'});const errors=[];page.on('pageerror',e=>errors.push(e.message));
 for(const path of ['/','/news','/achievements','/officers']){
  const response=await page.goto('http://127.0.0.1:8005'+path,{waitUntil:'networkidle'});
  const state=await page.evaluate(()=>({background:document.querySelectorAll('.site-ribbons i').length,fixed:getComputedStyle(document.querySelector('.site-ribbons')).position,overflow:document.documentElement.scrollWidth>innerWidth}));
  console.log(JSON.stringify({width,path,status:response.status(),...state,errors}));if(response.status()!==200||state.background!==6||state.overflow||errors.length)process.exitCode=1;
 }
 await page.screenshot({path:`storage/app/private/site-background-officers-${width}.png`});
 await page.goto('http://127.0.0.1:8005/',{waitUntil:'networkidle'});await page.locator('.light-section').scrollIntoViewIfNeeded();await page.screenshot({path:`storage/app/private/site-background-sections-${width}.png`});await page.close();
}await browser.close()})().catch(e=>{console.error(e);process.exit(1)});
