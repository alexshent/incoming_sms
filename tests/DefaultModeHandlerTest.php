<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class DefaultModeHandlerTest extends TestCase
{

    public function testParseNumberFromMessage1()
    {
        $message = ' 91.560.07.04. Тел Килинг чаттан чикиб кетяпман';
        $expected = '915600704';

        $handler = new DefaultModeHandler();
        $actual = $handler->parseNumberFromMessage($message);

        $this->assertEquals($expected, $actual);
    }

    public function testParseNumberFromMessage2()
    {
        $message = '919599616';
        $expected = '919599616';

        $handler = new DefaultModeHandler();
        $actual = $handler->parseNumberFromMessage($message);

        $this->assertEquals($expected, $actual);
    }

    public function testParseNumberFromMessage3()
    {
        $message = ' 91 639 37 16. Сикилганизга тел килинг гаплашгиз келса';
        $expected = '916393716';

        $handler = new DefaultModeHandler();
        $actual = $handler->parseNumberFromMessage($message);

        $this->assertEquals($expected, $actual);
    }

    public function testParseNumberFromMessage4()
    {
        $message = 'салом жоним 91 640 69 60 кутаман';
        $expected = '916406960';

        $handler = new DefaultModeHandler();
        $actual = $handler->parseNumberFromMessage($message);

        $this->assertEquals($expected, $actual);
    }

    public function testParseNumberFromMessage5()
    {
        $message = ' shu998911545677 maniki. Tel qilasiz ishonaman gapizga';
        $expected = '998911545677';

        $handler = new DefaultModeHandler();
        $actual = $handler->parseNumberFromMessage($message);

        $this->assertEquals($expected, $actual);
    }

    public function testParseNumberFromMessage6()
    {
        $message = " Assalomu aleko'm";
        $expected = null;

        $handler = new DefaultModeHandler();
        $actual = $handler->parseNumberFromMessage($message);

        $this->assertEquals($expected, $actual);
    }

    public function testParseNumberFromMessage7()
    {
        $message = '919442399';
        $expected = '919442399';

        $handler = new DefaultModeHandler();
        $actual = $handler->parseNumberFromMessage($message);

        $this->assertEquals($expected, $actual);
    }

    public function testParseNumberFromMessage8()
    {
        $message = ' qaleysiz?shu998911545677 meniki. Chatga yaxshi tushunmayabman. Qaytarvoring';
        $expected = '998911545677';

        $handler = new DefaultModeHandler();
        $actual = $handler->parseNumberFromMessage($message);

        $this->assertEquals($expected, $actual);
    }

    public function testParseNumberFromMessage9()
    {
        $message = "ASSALOMU ALEKO'M! QALAYSIZ UYDAGILARIZ YAXSHIMI SIZ BILAN TANISHSAK BO'LADIMI QAYERDAN SIZ ISMIZ NIMA NECHINCHI YILSIZ TURMUSHGA CHIQANMISIZ: MENBUXORODAN ISMIM BAHODIRZODA 15.09.1980- YILDA TUG'ILGAN MAN: AGAR MEN BILAN JIDDIY MAQSADDA GAPLASHMOQCHI BO'LSANGIZ MENI NOMERIMGA TEL YOKI SMS YOZISHIZMUMKIN: +998500773500";
        $expected = '998500773500';

        $handler = new DefaultModeHandler();
        $actual = $handler->parseNumberFromMessage($message);

        $this->assertEquals($expected, $actual);
    }
}
