"use strict";

window.Vue  = require('vue/dist/vue.min');
window.queryString = require('query-string');
window.Push = require('push.js');
window.store2 = require('store2');

Vue.config.productionTip = false;

window.onload = () =>
{
  const cursor = document.getElementById("cursor");
              
  if(cursor !== null)
  {
    let cursorTimeout;
    
    document.addEventListener("mousemove", (e) => 
    {
      let x = e.clientX;
      let y = e.clientY;
      
      cursor.style.top = y + "px";
      cursor.style.left = x + "px";
      cursor.style.display = "block";
      
      function mouseStopped() 
      {
        cursor.style.display = "none";
      }
      
      clearTimeout(cursorTimeout) ;
      
      cursorTimeout = setTimeout(mouseStopped, 5000) ;
    });

    document.addEventListener("mouseout", () => 
    {
      cursor.style.display = "none";
    });
  }  
}

window.sleep = (ms) => 
{
    return new Promise(resolve => setTimeout(resolve, ms));
}

window.resizeAllGridItems = function(selector)
{
	$(selector).removeClass('ui').addClass('masonry');
  
	let grid = $(selector)[0];

	let rowHeight = parseInt(window.getComputedStyle(grid).getPropertyValue('grid-auto-rows'));
  let rowGap = parseInt(window.getComputedStyle(grid).getPropertyValue('grid-row-gap'));

  let allItems = grid.querySelectorAll(".masonry-item");

  for(let x=0; x<allItems.length; x++)
  {
    let rowSpan = Math.ceil((allItems[x].querySelector('.card').getBoundingClientRect().height+rowGap)/(rowHeight+rowGap));
  	allItems[x].style.gridRowEnd = "span "+rowSpan;
  }
}

window.Carousel = class
{
  slider = null;
  isDown = false;
  start;
  scrollTop;
  vertical = false;

  constructor(selector, scrollingSpeed = 1.5, vertical = false, callback = null)
  {
    this.vertical = vertical;
    this.slider = document.querySelector(selector);

    if(this.slider == null || this.slider == undefined)
    {
      return;
    }

    this.slider.addEventListener('mousedown', (e) => 
    {
      this.isDown     = true;

      this.start  = this.vertical ? (e.pageY - this.slider.offsetTop) : (e.pageX - this.slider.offsetLeft);
      this.scroll = this.vertical ? this.slider.scrollTop : this.slider.scrollLeft;
    });

    this.slider.addEventListener('mouseleave', () => 
    {
      this.isDown = false;
    });

    this.slider.addEventListener('mouseup', () => 
    {
      this.isDown = false;
    });

    this.slider.addEventListener('mousemove', (e) => 
    {
      if(!this.isDown) 
      {
        return;
      }

      e.preventDefault();

      const a = this.vertical ? (e.pageY - this.slider.offsetTop) : (e.pageX - this.slider.offsetLeft);

      const walk = (a - this.start) * scrollingSpeed;

      if(this.vertical)
      {
        this.slider.scrollTop = this.scroll - walk; 
      }
      else
      {
        this.slider.scrollLeft = this.scroll - walk;
      }
      
    });

    if(callback !== null)
    {
      callback();
    }
  }
}

window.getObjectProp = function(obj, prop)
{
  if(obj === null)
    return null;

  if(obj.hasOwnProperty(prop))
    return obj[prop];

  return null;
}


window.getObjProps = function(obj, props)
{
  var props_ = {};

  for(var prop of props)
  {
    props_[prop] = obj[prop];
  }

  return props_;
}


window.formatTime = function(maxTime, currentTime) 
{
    let time = maxTime - currentTime;

    return [
        Math.floor((time % 3600) / 60), // minutes
        ('00' + Math.floor(time % 60)).slice(-2) // seconds
    ].join(':');
};



$.fn.isVisible = function(checkDisplay = false)
{
  var styles  = getComputedStyle($(this)[0]);
  var visible = styles.visibility === 'visible';

  if(!checkDisplay)
  {
    return visible;
  }
  else
  {
    return visible && styles.display !== 'none';
  }
}


$.fn.replaceClass = function(oldClass, newClass)
{
  if(this.hasClass(oldClass))
    this.removeClass(oldClass).addClass(newClass);
};


window.parseJson = function(jsonStr)
{
    var res;

    try
    {
      res = JSON.parse(jsonStr);
    }
    catch(e){}

    return res === undefined ? false : res;
}

window.duration = (toDate)=>
{
    let diffTime = Math.abs(new Date().valueOf() - new Date(toDate).valueOf());
    let days     = diffTime / (24*60*60*1000);
    let hours    = (days % 1) * 24;
    let minutes  = (hours % 1) * 60;
    let seconds     = (minutes % 1) * 60;

    [days, hours, minutes, seconds] = [Math.floor(days), Math.floor(hours), Math.floor(minutes), Math.floor(seconds)]

    return {days, hours, minutes, seconds};
}


window.startPromoCounter = function(countSelector, containerSelector)
{ 
    let props = ['days', 'hours', 'minutes', 'seconds'];

    function getCountText(toTime)
    {
        let times = duration(toTime);
        let text  = [];

        for(let i of props)
        {
          if(times.hasOwnProperty(i))
          {
            text.push(String(times[i]).length === 1 ? `0${String(times[i])}` : String(times[i]));
          }

          if(/hours|minutes|seconds/i.test(i) && !times.hasOwnProperty(i))
          {
            text.push('00'); 
          }
        }

        return text.join(':');
    }

    $(countSelector).each(function()
    {
      let $this = $(this);
      let time = parseJson(Base64.decode($this.data('json')));  

      if(!(typeof time === 'object' && time !== null))
      {
        $this.hide();
        return;
      }

      $this.text(getCountText(time.to));

      let timeInterval = setInterval(() =>
      {
        if(new Date(time.to).getTime() <= new Date().getTime())
        {
          clearInterval(timeInterval);
          $this.closest(containerSelector).remove();
        }

        let times = duration(time.to);

        let text  = [];

        for(let i of props)
        {
          if(times.hasOwnProperty(i))
          {
            text.push(String(times[i]).length === 1 ? `0${String(times[i])}` : String(times[i]));
          }

          if(/hours|minutes|seconds/i.test(i) && !times.hasOwnProperty(i))
          {
            text.push('00'); 
          }
        }

        $this.text(text.join(':'));
      }, 1000)
    })
}


window.debounce = function(func, wait, immediate) 
{
  var timeout;

  return function() 
  {
    var context = this, args = arguments;
    var later = function() 
    {
      timeout = null;
      if (!immediate) func.apply(context, args);
    };

    var callNow = immediate && !timeout;

    clearTimeout(timeout);

    timeout = setTimeout(later, wait);

    if (callNow) func.apply(context, args);
  };
};


Number.prototype.formatSeconds = function()
{
  let date = new Date(1970,0,1);
      date.setSeconds(this);
  
  return date.toTimeString().replace(/.*(\d{2}:\d{2}:\d{2}).*/, "$1");
}


String.prototype.shorten = function(limit = 100)
{
  return this.length > limit ? (this.slice(0, limit)+'...') : this;
}

window.canShare = () =>
{
  try 
  {
    return navigator.canShare();
  }
  catch(e)
  {
    return false;
  }
}