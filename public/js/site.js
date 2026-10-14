(() => {
'use strict';
const reduced = matchMedia('(prefers-reduced-motion: reduce)').matches;
const gs = window.gsap, st = window.ScrollTrigger;
if (gs && st) gs.registerPlugin(st);
const nav = document.querySelector('.navbar');
const toggle = document.querySelector('.menu-toggle');
toggle?.addEventListener('click', () => {
 const open = toggle.getAttribute('aria-expanded') !== 'true';
 toggle.setAttribute('aria-expanded', String(open)); toggle.setAttribute('aria-label', open ? 'Close navigation' : 'Open navigation');
 toggle.textContent = open ? '×' : '☰'; document.querySelector('#main-nav').classList.toggle('open',open);
});
document.addEventListener('keydown', e => {if(e.key==='Escape' && toggle?.getAttribute('aria-expanded')==='true') toggle.click();});
let pending=false;
function scrollUpdate(){
 nav?.classList.toggle('scrolled',scrollY>50);
 const progress=document.querySelector('.reading-progress');
 if(progress) progress.style.width=(100*scrollY/Math.max(1,document.documentElement.scrollHeight-innerHeight))+'%';
 pending=false;
}
window.addEventListener('scroll',()=>{if(!pending){pending=true;requestAnimationFrame(scrollUpdate);}},{passive:true}); scrollUpdate();
function hero(){
 if(!gs || reduced)return;
 gs.from('.hero-background.is-active',{scale:1.05,duration:2,ease:'power2.out'});
 gs.from('.headline-line',{y:35,opacity:0,duration:.85,stagger:.14,ease:'power3.out',clearProps:'all'});
 gs.from('.hero-reveal',{y:18,opacity:0,duration:.7,stagger:.12,delay:.25,clearProps:'all'});
 gs.from('.navbar',{y:-20,opacity:0,duration:.6,clearProps:'all'});
}
const heroSlides=[...document.querySelectorAll('.hero-background')];
if(heroSlides.length>1){
 let slideIndex=0, paused=false, timer;
 const status=document.querySelector('[data-slide-status]');
 const scheduleNext=()=>{
  clearTimeout(timer);
  if(paused)return;
  const current=heroSlides[slideIndex];
  if(current.tagName==='VIDEO'){
   if(current.ended)showSlide(slideIndex+1);
   return;
  }
  timer=setTimeout(()=>showSlide(slideIndex+1),7000);
 };
 const showSlide=(index)=>{
  slideIndex=(index+heroSlides.length)%heroSlides.length;
  heroSlides.forEach((slide,i)=>{slide.classList.toggle('is-active',i===slideIndex);if(slide.tagName==='VIDEO'){if(i===slideIndex){slide.currentTime=0;slide.play().catch(()=>{});}else{slide.pause();}}});
  if(status)status.textContent=`${slideIndex+1} / ${heroSlides.length}`;
  if(gs&&!reduced)gs.fromTo(heroSlides[slideIndex],{scale:1.03},{scale:1,duration:.8,ease:'power2.out'});
  scheduleNext();
 };
 heroSlides.forEach(slide=>{if(slide.tagName==='VIDEO')slide.addEventListener('ended',()=>{if(slide===heroSlides[slideIndex]&&!paused)showSlide(slideIndex+1);});});
 document.querySelector('[data-slide-prev]')?.addEventListener('click',()=>showSlide(slideIndex-1));
 document.querySelector('[data-slide-next]')?.addEventListener('click',()=>showSlide(slideIndex+1));
 document.querySelector('[data-slide-pause]')?.addEventListener('click',e=>{paused=!paused;e.currentTarget.textContent=paused?'▶':'Ⅱ';e.currentTarget.setAttribute('aria-label',paused?'Play cover images':'Pause cover images');e.currentTarget.setAttribute('aria-pressed',String(paused));scheduleNext();});
 scheduleNext();
}
const splash=document.querySelector('#splash');
let intro,finished=false,previousFocus;
function completeIntro(){
 if(finished)return;finished=true;intro?.kill();
 if(splash) splash.hidden=true;
 document.documentElement.classList.remove('intro-started');
 document.documentElement.classList.remove('intro-pending');
 document.querySelectorAll('[data-intro-inert]').forEach(el=>{el.inert=false;el.removeAttribute('data-intro-inert');});
 document.body.style.overflow='';
 try{sessionStorage.setItem('uecfi-intro','1');}catch{}
 previousFocus?.focus({preventScroll:true}); hero();
}
if(splash){
 let seen=false;try{seen=sessionStorage.getItem('uecfi-intro')==='1';}catch{}
 if((!seen||new URLSearchParams(location.search).get('show-intro')==='1')&&!reduced&&gs&&!location.search.includes('editor-preview')){
  document.documentElement.classList.add('intro-started');
  previousFocus=document.activeElement;splash.hidden=false;document.body.style.overflow='hidden';
  // Keep only the intro accessible while it is visible.
  document.querySelectorAll('body > header, body > footer').forEach(el=>{el.inert=true;el.setAttribute('data-intro-inert','');});
  [...document.querySelector('#main').children].filter(el=>el!==splash).forEach(el=>{el.inert=true;el.setAttribute('data-intro-inert','');});
  document.querySelector('#skip-intro').focus();
  intro=gs.timeline({onComplete:completeIntro});
  intro.from('.splash-glow',{opacity:0,duration:.7},0)
   .fromTo('.splash-logo',{opacity:0,scale:.82,filter:'blur(12px)'},{opacity:1,scale:1,filter:'blur(0px)',duration:.65,ease:'power2.out'},.1)
   .fromTo('.splash-welcome',{opacity:0,y:14},{opacity:1,y:0,duration:.5,ease:'power2.out'},.22)
   .to('.star-flash',{opacity:1,scale:40,rotation:30,duration:.6,ease:'power3.in'},4.8)
   .to(splash,{opacity:0,duration:.25},5.4);
  document.querySelector('#skip-intro').addEventListener('click',completeIntro);
  document.addEventListener('keydown',e=>{if(!splash.hidden&&e.key==='Escape')completeIntro();if(!splash.hidden&&e.key==='Tab'){e.preventDefault();document.querySelector('#skip-intro').focus();}});
  // Always reveal the real page even if an animation is interrupted.
  setTimeout(completeIntro,6200);
 }else{finished=true;document.documentElement.classList.remove('intro-pending');hero();}
}else hero();
if(gs&&st&&!reduced){
 gs.utils.toArray('.timeline').forEach(el=>gs.fromTo(el,{'--timeline-progress':0},{'--timeline-progress':1,ease:'none',scrollTrigger:{trigger:el,start:'top 70%',end:'bottom 85%',scrub:1}}));
 gs.utils.toArray('[data-reveal]').forEach(el=>gs.from(el,{opacity:0,y:25,duration:.7,ease:'power2.out',scrollTrigger:{trigger:el,start:'top 94%',once:true},clearProps:'all'}));
 document.querySelectorAll('[data-count]').forEach(el=>{
  const goal=Number(el.dataset.count),state={value:0};
  gs.to(state,{value:goal,duration:1.6,ease:'power2.out',scrollTrigger:{trigger:el,start:'top 95%',once:true},onUpdate:()=>el.textContent=Math.round(state.value).toLocaleString()});
 });
 gs.utils.toArray('.gold-line').forEach(el=>gs.from(el,{scaleX:0,transformOrigin:'left',scrollTrigger:{trigger:el,start:'top 90%'},duration:1}));
 if(matchMedia('(min-width: 900px) and (pointer:fine)').matches){
  gs.to('.hero-background',{y:80,ease:'none',scrollTrigger:{trigger:'.hero',start:'top top',end:'bottom top',scrub:1}});
  document.querySelectorAll('.bento .content-card').forEach(card=>{
   card.addEventListener('pointermove',e=>{const r=card.getBoundingClientRect();gs.to(card,{rotateY:(e.clientX-r.left-r.width/2)/r.width*3,rotateX:-(e.clientY-r.top-r.height/2)/r.height*3,duration:.3,transformPerspective:900});});
   card.addEventListener('pointerleave',()=>gs.to(card,{rotateX:0,rotateY:0,duration:.4}));
  });
 }
}
const dialogs=document.querySelectorAll('dialog');
document.querySelectorAll('[data-dialog]').forEach(button=>button.addEventListener('click',()=>document.getElementById(button.dataset.dialog)?.showModal()));
dialogs.forEach(dialog=>{
 dialog.querySelector('[data-close]')?.addEventListener('click',()=>dialog.close());
 dialog.addEventListener('click',e=>{if(e.target===dialog){const r=dialog.getBoundingClientRect();if(e.clientX<r.left||e.clientX>r.right||e.clientY<r.top||e.clientY>r.bottom)dialog.close();}});
});
const mediaButtons=[...document.querySelectorAll('[data-gallery]')],lightbox=document.querySelector('#lightbox');let mediaItems=[],imageIndex=0;
const mediaImage=lightbox?.querySelector('[data-lightbox-image]'),mediaVideo=lightbox?.querySelector('[data-lightbox-video]');
function showMedia(index){
 if(!mediaItems.length)return;
 imageIndex=(index+mediaItems.length)%mediaItems.length;
 const item=mediaItems[imageIndex],video=item.dataset.kind==='video';
 mediaVideo.pause();mediaVideo.removeAttribute('src');mediaVideo.load();mediaImage.removeAttribute('src');
 mediaImage.hidden=video;mediaVideo.hidden=!video;
 if(video){mediaVideo.src=item.dataset.gallery;mediaVideo.load();mediaVideo.play().catch(()=>{});}else{mediaImage.src=item.dataset.gallery;mediaImage.alt=item.dataset.alt||'';}
 lightbox.querySelector('.lightbox-caption').textContent=item.dataset.alt||'';
 lightbox.querySelector('.gallery-counter').textContent=`${imageIndex+1} / ${mediaItems.length}`;
 if(gs&&!reduced)gs.fromTo(video?mediaVideo:mediaImage,{opacity:0,scale:.98},{opacity:1,scale:1,duration:.25});
}
mediaButtons.forEach(button=>button.addEventListener('click',()=>{
 const group=button.dataset.mediaGroup;
 mediaItems=mediaButtons.filter(item=>group?item.dataset.mediaGroup===group:item===button);
 showMedia(mediaItems.indexOf(button));lightbox.showModal();
}));
lightbox?.querySelector('[data-prev]').addEventListener('click',()=>showMedia(imageIndex-1));
lightbox?.querySelector('[data-next]').addEventListener('click',()=>showMedia(imageIndex+1));
lightbox?.querySelector('[data-close]').addEventListener('click',()=>lightbox.close());
lightbox?.addEventListener('close',()=>{mediaVideo.pause();mediaVideo.removeAttribute('src');mediaVideo.load();});
lightbox?.addEventListener('keydown',e=>{if(e.key==='ArrowRight')showMedia(imageIndex+1);if(e.key==='ArrowLeft')showMedia(imageIndex-1);});
if(gs&&!reduced){
 document.querySelectorAll('a[href]').forEach(a=>a.addEventListener('click',e=>{
  if(e.defaultPrevented||e.button!==0||e.metaKey||e.ctrlKey||e.shiftKey||e.altKey||a.target||a.hasAttribute('download'))return;
  const url=new URL(a.href,location.href);
  if(url.origin!==location.origin||url.hash||url.pathname===location.pathname||!/^\/(news|achievements|activities|officers|admin\/login)(\/|$)/.test(url.pathname))return;
  e.preventDefault();const wipe=document.querySelector('.page-wipe');
  wipe.style.background=url.pathname.startsWith('/achievements')?'#b89543':'#102954';
  gs.to(wipe,{y:'-100%',duration:.35,ease:'power2.in',onComplete:()=>location.assign(url.href)});
 }));
}
window.addEventListener('pageshow',e=>{if(e.persisted&&gs)gs.set('.page-wipe',{clearProps:'all'});});
})();
