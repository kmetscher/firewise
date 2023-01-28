const makeVisible = (elementId) => {

    const opts = {
        root: null,
        rootMargin: "0%",
        threshold: 0.5,
    };

    const visibleCallback = (stages) => {
        stages.forEach((stage) => {
            if (stage.isIntersecting) {
                stage.target.setAttribute("class", "tile");
            }
        });
    };

    const observer = new IntersectionObserver(visibleCallback, opts);
    observer.observe(document.querySelector(elementId));

}

export default makeVisible;

