var UI      = UI        || {}

//////////////////////////////////////////////////////////////////////////////////
//              Constructor                                                     //
//////////////////////////////////////////////////////////////////////////////////

/**
 * create a plane on which we map 2d text
 */
UI.BadgeSprite        = function(){
        var width = 512
        var height = 256

	var canvas	= document.createElement( 'canvas' )
	canvas.width	= width
	canvas.height	= height
        this.canvas	= canvas

	//Store width and height
	this.textureWidth = width;
	this.textureHeight = height;


	var context	= canvas.getContext( '2d' )
	this.context	= context

	var texture	= new THREE.Texture(canvas)
	this.texture	= texture


        // Create the object
        var material = new THREE.SpriteMaterial({
                transparent: true,
                map     : texture
        });
        THREE.Sprite.call( this, material )

        this.scale.set(2,1,1)
}

UI.BadgeSprite.prototype = Object.create( THREE.Sprite.prototype );

/**
 * Draw the cartouche
 * @param  {Object} params [description]
 */
UI.BadgeSprite.prototype.draw = function(params){
	var context	= this.context
	var canvas	= this.canvas
        var texture     = this.texture

	context.save()

        // clear texture
	context.clearRect(0,0,this.canvas.width, this.canvas.height)

        // Draw white background
	var cornerRadius = 10;
	context.fillStyle = 'rgba(255,255,255,0.5)';
	context.fillRect(0+(cornerRadius/2), 0+(cornerRadius/2), canvas.width-cornerRadius, canvas.height-cornerRadius);

        // Draw background outlining
	context.lineJoin = 'round';
	context.lineWidth = cornerRadius;
	context.strokeStyle = '#0099ff';
	context.strokeRect(0+(cornerRadius/2), 0+(cornerRadius/2), canvas.width-cornerRadius, canvas.height-cornerRadius);

	// Draw avatar
	var avatarObject = new Image();
	avatarObject.width = 200;
	avatarObject.height = 200;
	avatarObject.style.width = '200px';
	avatarObject.style.height = '200px';
	avatarObject.onload = function() {
		context.drawImage(avatarObject, 20, 28, 200, 200);
        	// make the texture as .needsUpdate
        	texture.needsUpdate	= true;
	};
	avatarObject.src = params.avatar;
	avatarObject.crossOrigin = 'Anonymous';

	// draw avatar outlining
	context.rect(20,28,200,200);
	context.lineWidth = 1;
	context.strokeStyle = '#ffffff';
	context.stroke();



	// Write First Name
	writeText(params.firstName,235,50,16,'normal');
	// write Last Name
	writeText(params.lastName,235,110,25,'bold');


	// Write role label
	writeText(params.role,270,215,25,'normal');

	// Draw role icon
	var iconObject = new Image();
	iconObject.width = 50;
	iconObject.height = 50;
	iconObject.style.width = 50+'px';
	iconObject.style.height = 50+'px';
	iconObject.onload = function() {
		context.drawImage(iconObject, 220, 170, 50, 50);

        	// make the texture as .needsUpdate
        	texture.needsUpdate	= true;
	}
	if(params.role.toLowerCase() === 'pneumatic'){
		iconObject.src = 'features/role-icons/pneumatic.png';
	}else if(params.role.toLowerCase() === 'electric'){
		iconObject.src = 'features/role-icons/electric.png';
	}else if(params.role.toLowerCase() === 'mechanic'){
		iconObject.src = 'features/role-icons/mechanic.png';
	}else{
		iconObject.src = 'features/role-icons/other.png';
	}



        // retore context
	context.restore()

	// make the texture as .needsUpdate
	texture.needsUpdate	= true;

        return

	function writeText(text, positionX, positionY,size,weight){
		context.font        = weight+' '+size+'px Arial';



		context.fillStyle	= '#000';
		context.fillText(text, positionX, positionY);
	}
}
