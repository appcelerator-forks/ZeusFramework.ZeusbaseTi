var images = [];
var imageCount = 13;
for(var i=0; i<imageCount; i++) {
		image = {
					square:'images/gallery/square_'+(i+1)+'.png', //this var you need only for static thumb
					image:'images/gallery/'+(i+1)+'.jpg',
					hires:'', //indicates whether or not the source image is in 2x resolution for retina displays. Use for remote images ONLY.
					note: (i+1)+' image note'
		};
		images[i] = image;
}


//if you have an remote/locale json. below an example
/*
var images = [
			 	{
					square:'images/gallery/square_1.png',
					image:'images/gallery/1.jpg',
					note: '1 image note'
				},
			 	{
					square:'images/gallery/square_2.png',
					image:'images/gallery/2.jpg',
					note: '2 image note'
				},
			 	{
					square:'images/gallery/square_3.png',
					image:'images/gallery/3.jpg',
					note: '3 image note'
				},
			 	{
					square:'images/gallery/square_4.png',
					image:'images/gallery/4.jpg',
					note: '4 image note'
				},
			 	{
					square:'images/gallery/square_5.png',
					image:'images/gallery/5.jpg',
					note: '5 image note'
				},
			 	{
					square:'images/gallery/square_6.png',
					image:'images/gallery/6.jpg',
					note: '6 image note'
				},
			];
*/