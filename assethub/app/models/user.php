use Illuminate\Database\Eloquent\Relations\HasMany;

// Add inside class User:
public function mediaFiles(): HasMany
{
    return $this->hasMany(MediaFile::class);
}
