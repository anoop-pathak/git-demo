'use strict';

/* * * * * *  NOTE * * * * * */
/* pdfUrl and appUrl are defined in inline script in public.plade.php*/

/* set workerSrc property . */
PDFJS.workerSrc = appUrl+'js/components/pdfjs-bower/dist/pdf.worker.js';

var totalPages = 1; /* totalpages */
var pdf = null; /* pdf */

/**
* @load pdf pages in view 
**/
function handlePages(page) {
    console.log('H page ==>', page);
    /*page's dimensions*/
    var viewport = page.getViewport( 4 );

    var canvas = document.getElementById('canvas');
    var context = canvas.getContext('2d');
    canvas.height = viewport.height;
    canvas.width = viewport.width;

    var renderContext = {
        canvasContext: context,
        viewport: viewport
    };
    
    page.render(renderContext);
}


/**
* @get pdf pages one by one
**/
function getPdfPages(pageNumber) {

    setTimeout(function() {
        PDFJS.getDocument(pdfUrl).then(function getPdf(_pdf) {
            /* to access pdf globally*/
            pdf = _pdf;

            console.log('pdf ===>', pdf);

            /*How many pages it has*/
            totalPages = pdf.numPages;

            gotoPage(pageNumber);

            // $(".curr_page").text(pageNumber);
            // $(".total_pages").text(totalPages);

            // currPage = pageNumber

            // /*Start with first page*/
            // pdf.getPage( pageNumber ).then( handlePages );

        });
    }, 2000);
}

function gotoPage(pageNumber) {
    $(".curr_page").text(pageNumber);
    $(".total_pages").text(totalPages);

    currPage = pageNumber
    
    /*Start with first page*/
    pdf.getPage( pageNumber ).then( handlePages );
}

function goToPage(pageNumber) {
    $(".curr_page").text(pageNumber);
    currPage = pageNumber

    /*Start with first page*/
    pdf.getPage( pageNumber ).then( handlePages );
};

/**
* @get pdf onload
**/
getPdfPages(1);
