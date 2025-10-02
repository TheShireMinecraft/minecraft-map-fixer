# minecraft-map-fixer
Fix custom maps broken by updating to MC 1.21.8

Broken maps caused errors like the following appearing in the server log:
```
[00:37:13 ERROR]: Error loading saved data: SavedDataType[map_16480]
java.lang.NullPointerException: Cannot invoke "net.minecraft.nbt.Tag.asNumber()" because "tag" is null
        at net.minecraft.nbt.NbtOps.getNumberValue(NbtOps.java:60)
        at net.minecraft.nbt.NbtOps.getNumberValue(NbtOps.java:27)
        at net.minecraft.resources.DelegatingOps.getNumberValue(DelegatingOps.java:51)
        at com.mojang.serialization.Codec$13.read(Codec.java:582)
        at com.mojang.serialization.codecs.PrimitiveCodec.decode(PrimitiveCodec.java:17)
```

This has been tested with PHP 8.3

- `composer install`
- Place maps in the `assets` directory
    - So the assets directory contains files named similarly to `map_2345.dat`
- Run the script `cd src && php index.php`
- Check the `assets/fixed` directory for any maps the script attempted to fix