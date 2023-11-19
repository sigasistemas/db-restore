<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */
namespace Callcocam\DbRestore\Traits;
 
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Set;
use Illuminate\Support\Str;

trait HasUploadFormField
{
    use FilesProcessing;

    public static function getUploadFormFieldset($name = 'file')
    {

        return Fieldset::make($name)
            ->label('Arquivo')
            ->columns(2)
            ->schema([
                static::getUploadFormField($name),
                static::getUploadUrlFormField(sprintf("external_%s", $name), $name)
            ]);
    }


    public static function getUploadFormField($name = 'file')
    {

        return FileUpload::make($name)
            ->label('Arquivo')
            ->columnSpanFull();
    }

    public static function getUploadUrlFormField($name = 'external_file', $file = 'file')
    {

        return TextInput::make($name)
            ->label('Url Externa do Arquivo (Opcional)')
            ->placeholder('https://www.google.com.br/arquivo.pdf')
            ->helperText('Informe a url do arquivo externo, de um arquivo hospedado em outro servidor exemplo: https://www.google.com.br/arquivo.pdf')
            ->columnSpanFull()
            ->suffixAction(
                Action::make('uploadExternalFile')
                    ->label('Upload Externo')
                    ->icon('heroicon-m-clipboard')
                    ->requiresConfirmation()
                    ->action(function (Set $set, $state) use ($file) {
                        $name = Str::afterLast($state, '/');
                        $ext = Str::afterLast($name, '.');
                        $name = Str::beforeLast($name, '.');
                        $name = Str::slug($name);
                        $name = sprintf("%s/%s.%s", get_tenant_id(), $name, $ext);
                        $result =  static::downloadFileFromUrl($state, $name);
                        if ($result) {
                            $set($file,$result);
                        }
                    })
            )
            ->maxLength(255);
    }

    public static function getUploadUrlFormFieldText($name = 'external_file', $file = 'file')
    {

        return TextInput::make($name)
            ->label('Url Externa do Arquivo (Opcional)')
            ->placeholder('https://www.google.com.br/arquivo.pdf')
            ->helperText('Informe a url do arquivo externo, de um arquivo hospedado em outro servidor exemplo: https://www.google.com.br/arquivo.pdf')
            ->columnSpanFull()
            ->suffixAction(
                Action::make('uploadExternalFile')
                    ->label('Upload Externo')
                    ->icon('heroicon-m-clipboard')
                    ->requiresConfirmation()
                    ->action(function (Set $set, $state) use ($file) {
                        $name = Str::afterLast($state, '/');
                        $ext = Str::afterLast($name, '.');
                        $name = Str::beforeLast($name, '.');
                        $name = Str::slug($name);
                        $name = sprintf("%s/%s.%s", get_tenant_id(), $name, $ext);
                        $result =  static::downloadFileFromUrl($state, $name);
                        if ($result) {
                            dd($result);
                            $set($file,$result);
                        }
                    })
            )
            ->maxLength(255);
    }
}
